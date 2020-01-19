<?php
namespace SakuraPanel;

use SakuraPanel;

class ProxyManager {
	
	public function getProxyInfo($id)
	{
		return Database::querySingleLine("proxies", Array("id" => $id));
	}
	
	public function getUserProxies($username)
	{
		$rs = Database::query("proxies", Array("username" => $username));
		
		if($rs) {
			$rs = Database::toArray($rs);
			return count($rs);
		} else {
			return -1;
		}
	}
	
	public function getProxiesByNode($node)
	{
		$rs = Database::query("proxies", Array("node" => $node));
		
		if($rs) {
			return Database::toArray($rs);
		} else {
			return [];
		}
	}
	
	public function getUserProxiesList($username)
	{
		$rs = Database::query("proxies", Array("username" => $username));
		
		if($rs) {
			return Database::toArray($rs);
		} else {
			return [];
		}
	}
	
	public function getTotalProxies()
	{
		$rs = Database::toArray(Database::query("proxies", Array()));
		return count($rs);
	}
	
	public function getRandomPort()
	{
		global $_config;
		
		$portList = [];
		
		for($i = $_config['proxies']['min'];$i < $_config['proxies']['max'];$i++) {
			$portList[$i] = $i;
		}
		
		$rs = Database::toArray(Database::query("proxies", Array()));
		
		foreach($rs as $proxy) {
			if(!empty($proxy['remote_port'])) {
				unset($portList[$proxy['remote_port']]);
			}
		}
		
		$resultList = [];
		foreach($portList as $port) {
			$resultList[] = $port;
		}
		
		return $resultList[mt_rand(0, count($resultList) - 1)];
	}
	
	public function isPortAvailable($port, $proxy_type, $node)
	{
		return Database::querySingleLine("proxies", Array("remote_port" => $port,
			"proxy_type" => $proxy_type, "node" => $node)) ? false : true;
	}
	
	public function isDomainAvailable($domain, $proxy_type, $node)
	{
		return Database::querySingleLine("proxies", Array("domain" => "[\"{$domain}\"]",
			"proxy_type" => $proxy_type, "node" => $node)) ? false : true;
	}
	
	public function isProxyNameExist($name)
	{
		return Database::querySingleLine("proxies", Array("username" => $_SESSION['user'],
			"proxy_name" => $name)) ? true : false;
	}
	
	public function addProxy($data)
	{
		$username            = $_SESSION['user'];
		$proxy_name          = $data['proxy_name'] ?? "MyProxy";
		$proxy_type          = $data['proxy_type'] ?? "tcp";
		$local_ip            = $data['local_ip'] ?? "127.0.0.1";
		$local_port          = $data['local_port'] ?? "80";
		$use_encryption      = $data['use_encryption'] ?? "false";
		$use_compression     = $data['use_compression'] ?? "false";
		$domain              = $data['domain'] ? "[\"{$data['domain']}\"]" : "";
		$locations           = $data['locations'] ?? "";
		$host_header_rewrite = $data['host_header_rewrite'] ?? "";
		$header_X_From_Where = $data['header_X_From_Where'] ?? "";
		$remote_port         = $data['remote_port'] ?? "";
		$sk                  = $data['sk'] ?? "";
		$status              = 0;
		$lastupdate          = time();
		$node                = $data['node'];
		
		return Database::insert("proxies", Array(
			"id"                  => null,
			"username"            => $username,
			"proxy_name"          => $proxy_name,
			"proxy_type"          => $proxy_type,
			"local_ip"            => $local_ip,
			"local_port"          => $local_port,
			"use_encryption"      => $use_encryption,
			"use_compression"     => $use_compression,
			"domain"              => $domain,
			"locations"           => $locations,
			"host_header_rewrite" => $host_header_rewrite,
			"header_X-From-Where" => $header_X_From_Where,
			"remote_port"         => $remote_port,
			"sk"                  => $sk,
			"status"              => $status,
			"lastupdate"          => $lastupdate,
			"node"                => $node
		));
	}
	
	// 基本上对所有的选项都进行了验证
	// 如有需要可以自己修改验证的规则
	public function checkRules($data)
	{
		global $_config;
		
		$nm    = new SakuraPanel\NodeManager();
		$um    = new SakuraPanel\UserManager();
		$_list = Array("node", "proxy_name", "proxy_type", "local_ip", "local_port", "remote_port", "domain");
		$_type = Array("tcp", "udp", "http", "https", "xtcp", "stcp");
		$max_proxies = Intval($um->getInfoByUser($_SESSION['user'])['proxies']);
		
		if($this->getUserProxies($_SESSION['user']) >= $max_proxies && $max_proxies !== -1) {
			return Array(false, "您已经达到限制，不能再创建更多隧道了。");
		}
		
		foreach($_list as $_item) {
			if(!isset($data[$_item])) {
				return Array(false, "基础信息部分所有选项都是必填的。");
			}
		}
		
		if(!Regex::isNumber($data['node'])) {
			return Array(false, "请求不合法，无效的节点");
		}
		
		if(!$nm->isNodeExist($data['node'])) {
			return Array(false, "请求不合法，节点不存在");
		}
		
		if(!Regex::isProxyName($data['proxy_name'])) {
			return Array(false, "隧道名称不合法，必须是英文和数字以及下划线组成");
		}
		
		if($this->isProxyNameExist($data['proxy_name'])) {
			return Array(false, "隧道 {$data['proxy_name']} 已存在，请使用其他名字");
		}
		
		if(!in_array($data['proxy_type'], $_type)) {
			return Array(false, "请求不合法，无效的隧道类型");
		}
		
		if(!filter_var($data['local_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return Array(false, "本地地址不合法，不是一个有效的 IPv4 地址");
		}
		
		if(!Regex::isNumber($data['local_port']) || Intval($data['local_port']) < 0 || Intval($data['local_port']) > 65535) {
			return Array(false, "本地端口不合法，必须是数字且大于 <code>0</code> 小于 <code>65535</code>。");
		}
		
		if($data['proxy_type'] !== "http" && $data['proxy_type'] !== "https") {
			
			if(!isset($data['remote_port']) || $data['remote_port'] == "") {
				return Array(false, strtoupper($data['proxy_type']) . " 类型的隧道必须要指定一个远程端口");
			}
			
			if(!Regex::isNumber($data['remote_port']) || Intval($data['remote_port']) < $_config['proxies']['min'] 
				|| Intval($data['remote_port']) > $_config['proxies']['max']) {
				return Array(false, "远程端口不合法，必须是数字且大于 <code>{$_config['proxies']['min']}</code> 小于 <code>{$_config['proxies']['max']}</code>。");
			}
			
			if(!$this->isPortAvailable($data['remote_port'], $data['proxy_type'], $data['node'])) {
				return Array(false, "此远程端口已经被其他隧道使用，请更换其他的端口。");
			}
			
			if($data['proxy_type'] == "xtcp" || $data['proxy_type'] == "stcp") {
				if(!isset($data['sk']) || $data['sk'] == "") {
					return Array(false, "创建 XTCP 或 STCP 类型的隧道时必须要指定访问密码");
				}
			}
			
		} else {
			
			if(!isset($data['domain']) || $data["domain"] == "") {
				return Array(false, "创建 HTTP 或 HTTPS 类型的隧道时必须要指定一个域名");
			}
			
			if(!$this->isDomainAvailable($data['domain'], $data['proxy_type'], $data['node'])) {
				return Array(false, "此域名已经被其他隧道使用，同一协议（http/https）一个域名只能添加一次。");
			}
		}
		
		if(isset($_config['proxies']['protect']) && !empty($_config['proxies']['protect'])) {
			
			foreach($_config['proxies']['protect'] as $key => $value) {
				if(Intval($data['remote_port']) >= $key && Intval($data['remote_port']) <= $value) {
					return Array(false, "该远程端口不可用，因为它属于系统保留端口范围，详情请点击端口规则查看。");
				}
			}
		}
		
		if(isset($data['domain']) && $data["domain"] !== "") {
			if(!Regex::isDomain($data['domain'])) {
				return Array(false, "域名格式错误，请输入一个有效并且真实存在的域名");
			}
		}
		
		// 剩下是高级设置
		
		if(isset($data['use_encryption']) && $data['use_encryption'] !== "") {
			if($data['use_encryption'] !== "true" && $data['use_encryption'] !== "false") {
				return Array(false, "连接加密选项只能是 true 和 false，不用试了");
			}
		}
		
		if(isset($data['use_compression']) && $data['use_compression'] !== "") {
			if($data['use_compression'] !== "true" && $data['use_compression'] !== "false") {
				return Array(false, "数据压缩选项只能是 true 和 false，不用试了");
			}
		}
		
		// 这里是判断 Locations 字段的，我的设定是开头必须是 /，长度 64 字符以内，后面的不管
		
		if(isset($data['locations']) && $data['locations'] !== "") {
			if(substr($data['locations'], 0, 1) !== "/" || strlen($data['locations']) > 64) {
				return Array(false, "URL 路由开头必须是 /，且最多不能超过 64 个字符");
			}
		}
		
		if(isset($data['host_header_rewrite']) && $data["host_header_rewrite"] !== "") {
			if(!Regex::isDomain($data['host_header_rewrite'])) {
				return Array(false, "Host 重写域名格式错误，请输入一个有效的域名");
			}
		}
		
		if(isset($data['header_X_From_Where']) && $data["header_X_From_Where"] !== "") {
			if(!Regex::isDomain($data['header_X_From_Where'])) {
				return Array(false, "请求来源必须是一个域名");
			}
		}
		
		if(isset($data['sk']) && $data["sk"] !== "") {
			if(strlen($data['sk']) < 5 || strlen($data['sk']) > 32) {
				return Array(false, "访问密码必须大于 5 个字符，小于 32 个字符。");
			}
		}
		
		return Array(true, "Check done");
	}
	
	public function getUserProxiesConfig($user, $node)
	{
		global $_config;
		
		$nm = new SakuraPanel\NodeManager();
		$um = new SakuraPanel\UserManager();
		
		if(!$um->checkUserExist($user)) {
			return Array(false, "用户不存在");
		}
		
		if(!$nm->isNodeExist($node)) {
			return Array(false, "节点不存在");
		}
		
		// 获取节点信息和用户 Token
		$ns = $nm->getNodeInfo($node);
		$tk = $um->getUserToken($user);
		
		// 客户端基础配置
		$configuration = <<<EOF
[common]
server_addr = {$ns['ip']}
server_port = {$ns['port']}
tcp_mux = true
protocol = tcp
user = {$tk}
token = {$ns['token']}
dns_server = 114.114.114.114


EOF;
		
		// 获取用户的所有隧道
		$list = $this->getUserProxiesList($user);
		
		foreach($list as $item) {
			
			// 如果不是此节点的忽略
			if(Intval($item[16]) !== Intval($node) || $item[14] !== "0") continue;
			
			// 防止出现 Bug
			$local_ip   = $item[4] == "" ? "127.0.0.1" : $item[4];
			$local_port = $item[5] == "" ? "80" : $item[5];
			
			// 隧道的基本信息
			$configuration .= <<<EOF
[{$item[2]}]
privilege_mode = true
type = {$item[3]}
local_ip = {$local_ip}
local_port = {$local_port}

EOF;

			if($item[3] == "http" || $item[3] == "https") {
				// HTTP / HTTPS
				$domain = json_decode($item[8], true);
				$configuration .= "custom_domains = {$domain[0]}\n";
				$configuration .= $item[9] == "" ? "" : "locations = {$item[9]}\n";
				$configuration .= $item[10] == "" ? "" : "host_header_rewrite = {$item[10]}\n";
				$configuration .= $item[13] == "" ? "" : "header_X-From-Where = {$item[13]}\n";
			} else {
				// TCP / UDP / XTCP / STCP
				$configuration .= "remote_port = {$item[11]}\n";
				$configuration .= $item[12] == "" ? "" : "sk = {$item[12]}\n";
			}
			
			// 压缩和加密
			$configuration .= $item[6] == "" ? "" : "use_encryption = {$item[6]}\n";
			$configuration .= $item[7] == "" ? "" : "use_compression = {$item[7]}\n";
			$configuration .= "\n";
		}
		
		return $configuration;
	}
}
