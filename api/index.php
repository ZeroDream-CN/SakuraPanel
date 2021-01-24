<?php
namespace SakuraPanel;

use SakuraPanel;

// API 密码，需要和 Frps.ini 里面设置的一样
define("API_TOKEN", "SakuraFrpToken");
define("ROOT", realpath(__DIR__ . "/../"));

if(ROOT === false) {
	exit("Please place this file on /api/ folder");
}

include(ROOT . "/configuration.php");
include(ROOT . "/core/Database.php");
include(ROOT . "/core/Regex.php");
include(ROOT . "/core/Utils.php");

$conn = null;
$db = new SakuraPanel\Database();

include(ROOT . "/core/UserManager.php");
include(ROOT . "/core/NodeManager.php");
include(ROOT . "/core/ProxyManager.php");

$pm = new ProxyManager();
$nm = new NodeManager();

// 服务端 API 部分
// 先进行 Frps 鉴权
if(isset($_GET['apitoken']) || (isset($_GET['action']) && $_GET['action'] == "getconf")) {
	
	if(isset($_GET['apitoken'])) {
		// 取得节点 ID
		$expToken = explode("|", $_GET['apitoken']);
		if(count($expToken) !== 2 || !preg_match("/^[0-9]{1,5}$/", $expToken[1])) {
			Utils::sendServerForbidden("Invalid Node ID");
		} elseif($expToken[0] !== API_TOKEN) {
			Utils::sendServerForbidden("Invalid API Token");
		}
		$switchNode = Intval($expToken[1]);
		if(!$nm->isNodeAvailable($switchNode)) {
			Utils::sendServerForbidden("This server is current not available");
		}
	}
	
	switch($_GET['action']) {
		case "getconf":
			// 精简了一下，用户名可以不用了
			if(isset($_GET['token'], $_GET['node'])) {
				if(Regex::isLetter($_GET['token']) && Regex::isNumber($_GET['node'])) {
					$rs = Database::querySingleLine("tokens", [
						"token" => $_GET['token']
					]);
					if($rs && $nm->isNodeExist($_GET['node'])) {
						$rs = $pm->getUserProxiesConfig($rs['username'], $_GET['node']);
						if(is_string($rs)) {
							Header("Content-Type: text/plain");
							exit($rs);
						} else {
							Utils::sendServerNotFound("User or node not found");
						}
					} else {
						Utils::sendServerNotFound("User or node not found");
					}
				} else {
					Utils::sendServerNotFound("Invalid token");
				}
			} else {
				Utils::sendServerNotFound("Invalid request");
			}
			break;
		
		// 检查客户端是否合法
		case "checktoken":
			if(isset($_GET['user'])) {
				if(Regex::isLetter($_GET['user']) && strlen($_GET['user']) == 16) {
					$userToken = Database::escape($_GET['user']);
					$rs = Database::querySingleLine("tokens", ["token" => $userToken]);
					if($rs) {
						$userName = Database::escape($rs['username']);
						if(!$nm->isUserHasPermission($userName, $switchNode)) {
							Utils::sendServerForbidden("You have no permission to connect this server");
						}
						Utils::sendLoginSuccessful("Login successful, welcome!");
					} else {
						Utils::sendServerForbidden("Login failed");
					}
				} else {
					Utils::sendServerForbidden("Invalid username");
				}
			} else {
				Utils::sendServerForbidden("Username cannot be empty");
			}
			break;
		
		// 检查隧道是否合法
		case "checkproxy":
			if(isset($_GET['user'])) {
				if(Regex::isLetter($_GET['user']) && strlen($_GET['user']) == 16) {
					$proxyName  = str_replace("{$_GET['user']}.", "", $_GET['proxy_name']);
					$proxyType  = $_GET['proxy_type'] ?? "tcp";
					$remotePort = Intval($_GET['remote_port']) ?? "";
					$sk         = Database::escape($_GET['sk'] ?? "");
					$userToken  = Database::escape($_GET['user']);
					$rs         = Database::querySingleLine("tokens", ["token" => $userToken]);
					if($rs) {
						if($proxyType == "tcp" || $proxyType == "udp") {
							if(isset($remotePort) && Regex::isNumber($remotePort)) {
								$username = Database::escape($rs['username']);
								// 这里只对远程端口做限制，可根据自己的需要修改
								$rs = Database::querySingleLine("proxies", [
									"username"    => $username,
									"remote_port" => $remotePort,
									"proxy_type"  => $proxyType,
									"node"        => $switchNode
								]);
								if($rs) {
									if($rs['status'] !== "0") {
										Utils::sendServerForbidden("Proxy disabled");
									}
									Utils::sendCheckSuccessful("Proxy exist");
								} else {
									Utils::sendServerNotFound("Proxy not found");
								}
							} else {
								Utils::sendServerBadRequest("Invalid request");
							}
						} elseif($proxyType == "stcp" || $proxyType == "xtcp") {
							if(isset($sk) && !empty($sk)) {
								$username = Database::escape($rs['username']);
								// 这里只对 SK 做限制，可根据自己的需要修改
								$rs = Database::querySingleLine("proxies", [
									"username"    => $username,
									"sk"          => $sk,
									"proxy_type"  => $proxyType,
									"node"        => $switchNode
								]);
								if($rs) {
									if($rs['status'] !== "0") {
										Utils::sendServerForbidden("Proxy disabled");
									}
									Utils::sendCheckSuccessful("Proxy exist");
								} else {
									Utils::sendServerNotFound("Proxy not found");
								}
							} else {
								Utils::sendServerBadRequest("Invalid request");
							}
						} elseif($proxyType == "http" || $proxyType == "https") {
							if(isset($_GET['domain']) || isset($_GET['subdomain'])) {
								// 目前只验证域名和子域名
								$domain    = $_GET['domain'] ?? "null";
								$subdomain = $_GET['subdomain'] ?? "null";
								$username  = $rs['username'];
								$domain    = $domain;
								$subdomain = $subdomain;
								$domainSQL = (isset($_GET['domain']) && !empty($_GET['domain'])) ? ["domain" => $domain] : ["subdomain" => $subdomain];
								$querySQL  = [
									"username"   => $username,
									"proxy_type" => $proxyType,
									"node"       => $switchNode
								];
								$querySQL  = Array_merge($querySQL, $domainSQL);
								$rs        = Database::querySingleLine("proxies", $querySQL);
								if($rs) {
									if($rs['status'] !== "0") {
										Utils::sendServerForbidden("Proxy disabled");
									}
									Utils::sendCheckSuccessful("Proxy exist");
								} else {
									Utils::sendServerNotFound("Proxy not found");
								}
							} else {
								Utils::sendServerBadRequest("Invalid request");
							}
						} else {
							Utils::sendServerBadRequest("Invalid request");
						}
					} else {
						Utils::sendServerNotFound("User not found");
					}
				} else {
					Utils::sendServerBadRequest("Invalid request");
				}
			} else {
				Utils::sendServerForbidden("Invalid username");
			}
			break;
		case "getlimit":
			if(isset($_GET['user'])) {
				if(Regex::isLetter($_GET['user']) && strlen($_GET['user']) == 16) {
					$userToken = Database::escape($_GET['user']);
					$rs = Database::querySingleLine("tokens", ["token" => $userToken]);
					if($rs) {
						$username = Database::escape($rs['username']);
						$ls       = Database::querySingleLine("limits", ["username" => $username]);
						if($ls) {
							Utils::sendJson(Array(
								'status' => 200,
								'max-in' => Floatval($ls['inbound']),
								'max-out' => Floatval($ls['outbound'])
							));
						} else {
							$uinfo = Database::querySingleLine("users", ["username" => $username]);
							if($uinfo) {
								if($uinfo['group'] == "admin") {
									Utils::sendJson(Array(
										'status' => 200,
										'max-in' => 1000000,
										'max-out' => 1000000
									));
								}
								$group = Database::escape($uinfo['group']);
								$gs    = Database::querySingleLine("groups", ["name" => $group]);
								if($gs) {
									Utils::sendJson(Array(
										'status' => 200,
										'max-in' => Floatval($gs['inbound']),
										'max-out' => Floatval($gs['outbound'])
									));
								} else {
									Utils::sendJson(Array(
										'status' => 200,
										'max-in' => 1024,
										'max-out' => 1024
									));
								}
							} else {
								Utils::sendServerForbidden("User not exist");
							}
						}
					} else {
						Utils::sendServerForbidden("Login failed");
					}
				} else {
					Utils::sendServerForbidden("Invalid username");
				}
			} else {
				Utils::sendServerForbidden("Username cannot be empty");
			}
			break;
		default:
			Utils::sendServerNotFound("Undefined action");
	}
} else {
	Utils::sendServerNotFound("Invalid request");
}
