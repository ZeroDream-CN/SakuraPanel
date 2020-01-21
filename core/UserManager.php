<?php
namespace SakuraPanel;

use SakuraPanel;

class UserManager {
	
	public function isLogged()
	{
		return (isset($_SESSION['user']) && !empty($_SESSION['user']));
	}
	
	public function doLogin($data)
	{
		if(empty($data['username']) || empty($data['password'])) {
			return Array("status" => false, "message" => "请将信息填写完整");
		}
		
		if(!$this->checkUserName($data['username'])) {
			return Array("status" => false, "message" => "用户名不合法");
		}
		
		// 获取用户的信息（以用户名）
		$rs = $this->getInfoByUser($data['username']);
		
		if(!$rs) {
			
			// 获取用户的信息（以邮箱）
			$rs = $this->getInfoByEmail($data['username']);
			if(!$rs) {
				return Array("status" => false, "message" => "用户名或密码错误");
			}
		}
		
		if(!$this->checkPassword($data['password'], $rs['password'])) {
			return Array("status" => false, "message" => "用户名或密码错误");
		}
		
		return Array("status" => true, "message" => "登录成功", "username" => $rs['username'], "email" => $rs['email']);
	}
	
	public function doRegister($data)
	{
		global $_config;
		
		if(!$_config['register']['enable']) {
			return Array("status" => false, "message" => "抱歉，本站暂不开放注册");
		}
		
		if($_config['register']['invite']) {
			if(!isset($data['invitecode']) || empty($data['invitecode']) || !preg_match("/^[A-Za-z0-9]{32}$/", $data['invitecode'])) {
				return Array("status" => false, "message" => "您需要填写正确的邀请码才能注册账号");
			} else {
				$inviteCode = $data['invitecode'];
			}
		} else {
			$inviteCode = false;
		}
		
		if(!isset($data['username']) || !isset($data['email']) || !isset($data['password']) ||
			empty($data['username']) || empty($data['email']) || empty($data['password'])) {
			return Array("status" => false, "message" => "请将信息填写完整");
		}
		
		if($_config['smtp']['enable']) {
			if(!isset($data['verifycode']) || empty($data['verifycode'])) {
				return Array("status" => false, "message" => "请输入验证码");
			} else {
				if(!isset($_SESSION['reg_verifycode']) || $_SESSION['reg_verifycode'] == "") {
					return Array("status" => false, "message" => "验证码已失效，请重新获取邮件");
				}
				if(isset($_SESSION['reg_wait'])) {
					if(time() - $_SESSION['reg_wait'] > 900) {
						exit("验证码已失效，请重新获取邮件");
					}
				}
				if($_SESSION['reg_email'] !== $data['email']) {
					return Array("status" => false, "message" => "请重新验证该邮箱后才能注册");
				}
				if(Intval($_SESSION['reg_verifycode']) !== Intval($data['verifycode'])) {
					return Array("status" => false, "message" => "验证码错误，请检查");
				}
			}
		}
		
		if(!$this->checkUserName($data['username'])) {
			return Array("status" => false, "message" => "用户名不合法");
		}
		
		if($this->checkUserExist($data['username'])) {
			return Array("status" => false, "message" => "该用户名已被注册");
		}
		
		if($this->checkEmailExist($data['email'])) {
			return Array("status" => false, "message" => "该邮箱已被注册");
		}
		
		if($inviteCode) {
			if(!$this->checkInviteCode($data['invitecode'])) {
				return Array("status" => false, "message" => "邀请码无效或已被使用");
			} else {
				Database::update("invitecode", Array("user" => $data['username']), Array("code" => $data['invitecode']));
			}
		}
		
		// 执行注册
		$this->addUser($data['username'], $data['email'], $data['password']);
		
		return Array("status" => true, "message" => "账号注册成功！");
	}
	
	public function resetPass($link)
	{
		$lk = Database::querySingleLine("findpass", Array("link" => $link));
		if($lk) {
			if(time() - $lk['time'] < 3600) {
				$newpass  = $this->getUserToken($lk['username']);
				$password = $this->generatePassword($newpass);
				$token    = substr(md5(sha1(md5($lk['username']) . md5($password) . time() . mt_rand(0, 9999999))), 0, 16);
				// 更新数据库
				Database::update("users", Array("password" => $password), Array("username" => $lk['username']));
				Database::update("tokens", Array("token" => $token), Array("username" => $lk['username']));
				Database::delete("findpass", Array("link" => $link));
				return true;
			}
			return false;
		}
		return false;
	}
	
	public function doFindpass($data)
	{
		global $_config;
		
		if(!$_config['smtp']['enable']) {
			return Array("status" => false, "message" => "本站未开启 SMTP 服务，请联系管理员");
		}
		
		if(isset($_SESSION['sendmail']) && time() - $_SESSION['sendmail'] <= 60) return Array("status" => false, "message" => "您操作的频率太高，请稍后再试");
		if($data['username'] == "") return Array("status" => false, "message" => "请填写要找回密码的账号或邮箱");
		
		$rs     = $this->getInfoByUser($data['username']);
		$link   = sha1(md5($rs['username'] . $rs['password'] . time() . mt_rand(0, 9999999)) . md5(mt_rand(0, 9999999)));
		$found = false;
		
		if($rs) {
			$this->sendFindpassEmail($rs['username'], $rs['email'], $link);
			$found = true;
		} else {
			$rs = $this->getInfoByEmail($data['username']);
			if($rs) {
				$this->sendFindpassEmail($rs['username'], $rs['email'], $link);
				$found = true;
			}
		}
		
		if($found) {
			$ms = Database::querySingleLine("findpass", Array("username" => $rs['username']));
			if($ms) {
				Database::delete("findpass", Array("username" => $rs['username']));
			}
			Database::insert("findpass", Array(
				"username" => $rs['username'],
				"link"     => $link,
				"time"     => time()
			));
		}
		
		$_SESSION['sendmail'] = time();
		
		return Array("status" => true, "message" => "我们已经尝试了发送一封邮件到该账号的邮箱，请查收。");
	}
	
	public function sendFindpassEmail($username, $email, $link)
	{
		global $_config;
		
		$type  = SakuraPanel\Utils::isHttps() ? "https" : "http";
		$token = $this->getUserToken($username);
		$url   = "{$type}://{$_SERVER['SERVER_NAME']}/?page=findpass&link={$link}";
		$temp  = @file_get_contents(ROOT . "/assets/email/findpass.html");
		
		$temp  = str_replace("{SITENAME}", $_config['sitename'], $temp);
		$temp  = str_replace("{SITEDESCRIPTION}", $_config['description'], $temp);
		$temp  = str_replace("{USERNAME}", $username, $temp);
		$temp  = str_replace("{TOKEN}", $token, $temp);
		$temp  = str_replace("{URL}", $url, $temp);
		
		$smtp  = new SakuraPanel\Smtp(
			$_config['smtp']['host'],
			$_config['smtp']['port'],
			true,
			$_config['smtp']['user'],
			$_config['smtp']['pass']
		);
		
		$smtp->debug = false;
		$smtp->sendMail($email, $_config['smtp']['mail'], "找回您的 {$_config['sitename']} 密码", $temp, "HTML");
	}
	
	public function sendRegisterEmail($email, $number)
	{
		global $_config;
		
		$temp  = @file_get_contents(ROOT . "/assets/email/welcome.html");
		
		$temp  = str_replace("{SITENAME}", $_config['sitename'], $temp);
		$temp  = str_replace("{SITEDESCRIPTION}", $_config['description'], $temp);
		$temp  = str_replace("{NUMBER}", $number, $temp);
		
		$smtp  = new SakuraPanel\Smtp(
			$_config['smtp']['host'],
			$_config['smtp']['port'],
			true,
			$_config['smtp']['user'],
			$_config['smtp']['pass']
		);
		
		$smtp->debug = false;
		$smtp->sendMail($email, $_config['smtp']['mail'], "验证您的 {$_config['sitename']} 账号", $temp, "HTML");
	}
	
	public function addUser($username, $email, $password, $group = "default", $status = 0)
	{
		global $_config;
		
		// 生成密码和 Token
		$password = $this->generatePassword($password);
		$token    = substr(md5(sha1(md5($username) . md5($password) . time() . mt_rand(0, 9999999))), 0, 16);
		
		Database::insert("users", Array(
			"id"       => null,
			"username" => $username,
			"password" => $password,
			"email"    => $email,
			"traffic"  => $_config['register']['traffic'],
			"proxies"  => $_config['register']['proxies'],
			"group"    => $group,
			"regtime"  => time(),
			"status"   => $status,
		));
		
		Database::insert("tokens", Array(
			"id"       => null,
			"username" => $username,
			"token"    => $token,
			"status"   => 0
		));
	}
	
	public function updateUser($id, $data)
	{
		$rs = $this->getInfoById($id);
		if(is_array($rs)) {
			$result = Database::update("users", Array(
				"traffic" => $data['traffic'],
				"proxies" => $data['proxies'],
				"group"   => $data['group'],
				"status"  => $data['status']
			), Array("id" => $id));
			
			if($result !== true) {
				return $result;
			}
			
			if(isset($data['inbound'], $data['outbound']) && SakuraPanel\Regex::isNumber($data['inbound']) && SakuraPanel\Regex::isNumber($data['outbound'])) {
				$ls = Database::querySingleLine("limits", Array("username" => $rs['username']));
				if($ls) {
					$result = Database::update("limits", Array(
						"inbound"  => $data['inbound'],
						"outbound" => $data['outbound']
					), Array("username" => $rs['username']));
				} else {
					$result = Database::insert("limits", Array(
						"username" => $rs['username'],
						"inbound"  => $data['inbound'],
						"outbound" => $data['outbound']
					));
				}
			} else {
				$result = Database::delete("limits", Array("username" => $rs['username']));
			}
			
			return $result;
		} else {
			return false;
		}
	}
	
	public function getTokensToUsers()
	{
		$rs = Database::toArray(Database::query("tokens", Array()));
		$tokens = [];
		for($i = 0;$i < count($rs);$i++) {
			$tokens[$rs[$i][2]] = $rs[$i][1];
		}
		return $tokens;
	}
	
	public function getTotalUsers()
	{
		$rs = Database::toArray(Database::query("users", Array()));
		return count($rs);
	}
	
	public function getInfoById($id)
	{
		return Database::querySingleLine("users", Array("id" => $id));
	}
	
	public function getInfoByUser($username)
	{
		return Database::querySingleLine("users", Array("username" => $username));
	}
	
	public function getInfoByEmail($email)
	{
		return Database::querySingleLine("users", Array("email" => $email));
	}
	
	public function checkUserExist($username)
	{
		return Database::querySingleLine("users", Array("username" => $username)) ? true : false;
	}
	
	public function checkUserName($username)
	{
		return preg_match("/^[A-Za-z0-9\_\-]{3,32}$/", $username) ? true : false;
	}
	
	public function checkEmailExist($email)
	{
		return Database::querySingleLine("users", Array("email" => $email)) ? true : false;
	}
	
	public function checkInviteCode($code)
	{
		$rs = Database::querySingleLine("invitecode", Array("code" => $code));
		return ($rs && empty($rs['user']));
	}
	
	public function checkEmail($email)
	{
		return preg_match("/^\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,48}$/", $email) ? true : false;
	}
	
	public function checkPassword($password, $encrypted)
	{
		return password_verify($password, $encrypted);
	}
	
	public function generatePassword($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}
	
	public function getUserToken($username)
	{
		$rs = Database::querySingleLine("tokens", Array("username" => $username));
		return $rs['token'] ?? false;
	}
	
	/**
	 *
	 * 这里返回的 type：0 表示无设置，默认 / 1 表示该用户有独立设定 / 2 表示该用户继承组设定
	 *
	 */
	public function getLimit($username)
	{
		$rs = Database::querySingleLine("limits", Array("username" => $username));
		if($rs) {
			return Array("inbound" => $rs['inbound'], "outbound" => $rs['outbound'], "type" => 1);
		} else {
			$us = Database::querySingleLine("users", Array("username" => $username));
			if($us) {
				$gs = Database::querySingleLine("groups", Array("name" => $us['group']));
				if($gs) {
					return Array("inbound" => $gs['inbound'], "outbound" => $gs['outbound'], "type" => 2);
				}
			}
			return Array("inbound" => 1024, "outbound" => 1024, "type" => 0);
		}
	}
	
	public function getTodayTraffic($username)
	{
		$rs = Database::querySingleLine("todaytraffic", Array("user" => $username));
		if($rs) {
			return $rs['traffic'];
		} else {
			return 0;
		}
	}
}
