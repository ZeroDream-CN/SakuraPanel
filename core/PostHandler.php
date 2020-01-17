<?php
namespace SakuraPanel;

use SakuraPanel;

class PostHandler {
	
	public function switcher($params)
	{
		global $_config;
		
		if(isset($params['action']) && preg_match("/^[A-Za-z0-9\_\-]{1,20}$/", $params['action'])) {
			switch($params['action']) {
				case "login":
					$um = new SakuraPanel\UserManager();
					$pages = new SakuraPanel\Pages();
					if($_config['recaptcha']['enable']) {
						if(!isset($_POST["g-recaptcha-response"]) || !Utils::reCAPTCHA($_POST["g-recaptcha-response"])) {
							$data = Array("status" => false, "message" => "reCAPTCHA 验证失败，请刷新重试");
							$pages->loadPage("login", $data);
							exit;
						}
					}
					$data = $um->doLogin($_POST);
					if(isset($data['status']) && $data['status'] === true) {
						$_SESSION['user'] = $data['username'];
						$_SESSION['mail'] = $data['email'];
						$_SESSION['token'] = md5(mt_rand(0, 999999) . time() . $data['username']);
						exit("<script>location='?page=panel';</script>");
					}
					$pages->loadPage("login", $data);
					break;
				case "register":
					$um = new SakuraPanel\UserManager();
					$pages = new SakuraPanel\Pages();
					if($_config['recaptcha']['enable']) {
						if(!isset($_POST["g-recaptcha-response"]) || !Utils::reCAPTCHA($_POST["g-recaptcha-response"])) {
							$data = Array("status" => false, "message" => "reCAPTCHA 验证失败，请刷新重试");
							$pages->loadPage("register", $data);
							exit;
						}
					}
					$data = $um->doRegister($_POST);
					$pages->loadPage("register", $data);
					break;
				case "sendmail":
					$um = new SakuraPanel\UserManager();
					if(!$_config['smtp']['enable']) {
						exit("本站未开启 SMTP 服务！");
					}
					if(isset($_SESSION['reg_wait'])) {
						if(time() - $_SESSION['reg_wait'] < 60) {
							exit("您的操作过于频繁，请稍后再试。");
						}
					}
					if(!isset($_POST['mail']) || $_POST['mail'] == "") {
						exit("请填写邮箱！");
					}
					if(!$um->checkEmail($_POST['mail'])) {
						exit("不正确的邮箱格式！");
					}
					$rand = mt_rand(100000, 999999);
					$_SESSION['reg_verifycode'] = $rand;
					$_SESSION['reg_wait'] = time();
					$_SESSION['reg_email'] = $_POST['mail'];
					
					$um->sendRegisterEmail($_POST['mail'], $rand);
					exit("系统已发送一封邮件至您的邮箱，请查收。");
					break;
				case "findpass":
					$um = new SakuraPanel\UserManager();
					$pages = new SakuraPanel\Pages();
					if($_config['recaptcha']['enable']) {
						if(!isset($_POST["g-recaptcha-response"]) || !Utils::reCAPTCHA($_POST["g-recaptcha-response"])) {
							$data = Array("status" => false, "message" => "reCAPTCHA 验证失败，请刷新重试");
							$pages->loadPage("findpass", $data);
							exit;
						}
					}
					$data = $um->doFindpass($_POST);
					$pages->loadPage("findpass", $data);
					break;
				case "addproxy":
					$um = new SakuraPanel\UserManager();
					$pm = new SakuraPanel\ProxyManager();
					if($um->isLogged()) {
						$result = $pm->checkRules($_POST);
						if(is_array($result) && isset($result[0])) {
							if($result[0]) {
								if($pm->addProxy($_POST)) {
									exit("隧道创建成功");
								} else {
									exit("隧道创建失败，请联系管理员：" . Database::fetchError());
								}
							} else {
								$msg = $result[1] ?? "未知错误";
								exit($msg);
							}
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				case "updatepass":
					$um = new SakuraPanel\UserManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						if(!isset($_POST['oldpass']) || !isset($_POST['newpass']) || !isset($_POST['newpass1'])
							|| $_POST['oldpass'] == "" || $_POST['newpass'] == "" || $_POST['newpass1'] == "") {
							exit("<script>alert('不完整的信息，请重新填写');location='?page=panel&module=profile';</script>");
						}
						$us = $um->getInfoByUser($_SESSION['user']);
						if($um->checkPassword($_POST['oldpass'], $us['password'])) {
							if(strlen($_POST['newpass']) < 5) exit("<script>alert('新密码不能少于 5 个字符，请重新输入');location='?page=panel&module=profile';</script>");
							if($_POST['newpass'] !== $_POST['newpass1']) exit("<script>alert('两次输入的密码不一致');location='?page=panel&module=profile';</script>");
							$password = $um->generatePassword($_POST['newpass']);
							$token    = substr(md5(sha1(md5($_SESSION['user']) . md5($password) . time() . mt_rand(0, 9999999))), 0, 16);
							// 更新数据库
							Database::update("users", Array("password" => $password), Array("username" => $_SESSION['user']));
							Database::update("tokens", Array("token" => $token), Array("username" => $_SESSION['user']));
							unset($_SESSION['user']);
							unset($_SESSION['mail']);
							unset($_SESSION['token']);
							exit("<script>alert('密码修改成功，请重新登录。');location='?';</script>");
						} else {
							exit("<script>alert('旧密码错误，请检查');location='?page=panel&module=profile';</script>");
						}
					} else {
						exit("<script>alert('登录会话已超时，请重新登录');location='?';</script>");
					}
					break;
				case "updateuser":
					$um = new SakuraPanel\UserManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						$us = $um->getInfoByUser($_SESSION['user']);
						if($us['group'] == "admin") {
							$valid = SakuraPanel\Regex::isValid($_POST, [
								'id'       => SakuraPanel\Regex::TYPE_NUMBER,
								'traffic'  => SakuraPanel\Regex::TYPE_NUMBER,
								'proxies'  => SakuraPanel\Regex::TYPE_NUMBER,
								'group'    => SakuraPanel\Regex::TYPE_LETTER,
								'status'   => SakuraPanel\Regex::TYPE_NUMBER,
							]);
							if($valid === true) {
								$update = $um->updateUser($_POST['id'], [
									'traffic'  => $_POST['traffic'],
									'proxies'  => $_POST['proxies'],
									'inbound'  => $_POST['inbound'] ?? "",
									'outbound' => $_POST['outbound'] ?? "",
									'group'    => $_POST['group'],
									'status'   => $_POST['status'],
								]);
								if($update === true) {
									exit("用户资料更新成功！");
								} else {
									Header("HTTP/1.1 404 Not Found");
									exit("该用户不存在！{$update}");
								}
							} else {
								Header("HTTP/1.1 404 Not Found");
								exit("提交的数据不合法！{$valid}");
							}
						} else {
							exit("你没有足够的权限这么做");
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				case "updatenode":
					$um = new SakuraPanel\UserManager();
					$nm = new SakuraPanel\NodeManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						$us = $um->getInfoByUser($_SESSION['user']);
						if($us['group'] == "admin") {
							$valid = SakuraPanel\Regex::isValid($_POST, [
								'id'          => SakuraPanel\Regex::TYPE_NUMBER,
								'name'        => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'description' => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'hostname'    => SakuraPanel\Regex::TYPE_HOSTNAME,
								'ip'          => SakuraPanel\Regex::TYPE_IPV4_V6,
								'port'        => SakuraPanel\Regex::TYPE_NUMBER,
								'admin_port'  => SakuraPanel\Regex::TYPE_NUMBER,
								'admin_pass'  => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'token'       => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'group'       => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'status'      => SakuraPanel\Regex::TYPE_NUMBER,
							]);
							if($valid === true) {
								$update = $nm->updateNode($_POST['id'], [
									'id'          => $_POST['id'],
									'name'        => $_POST['name'],
									'description' => $_POST['description'],
									'hostname'    => $_POST['hostname'],
									'ip'          => $_POST['ip'],
									'port'        => $_POST['port'],
									'admin_port'  => $_POST['admin_port'],
									'admin_pass'  => $_POST['admin_pass'],
									'token'       => $_POST['token'],
									'group'       => $_POST['group'],
									'status'      => $_POST['status'],
								]);
								if($update === true) {
									exit("节点信息更新成功！");
								} else {
									Header("HTTP/1.1 404 Not Found");
									exit("该节点不存在！{$update}");
								}
							} else {
								Header("HTTP/1.1 404 Not Found");
								exit("提交的数据不合法！{$valid}");
							}
						} else {
							exit("你没有足够的权限这么做");
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				case "addnode":
					$um = new SakuraPanel\UserManager();
					$nm = new SakuraPanel\NodeManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						$us = $um->getInfoByUser($_SESSION['user']);
						if($us['group'] == "admin") {
							$valid = SakuraPanel\Regex::isValid($_POST, [
								'name'        => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'description' => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'hostname'    => SakuraPanel\Regex::TYPE_HOSTNAME,
								'ip'          => SakuraPanel\Regex::TYPE_IPV4_V6,
								'port'        => SakuraPanel\Regex::TYPE_NUMBER,
								'admin_port'  => SakuraPanel\Regex::TYPE_NUMBER,
								'admin_pass'  => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'token'       => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'group'       => SakuraPanel\Regex::TYPE_NOTEMPTY,
								'status'      => SakuraPanel\Regex::TYPE_NUMBER,
							]);
							if($valid === true) {
								$update = $nm->addNode([
									'name'        => $_POST['name'],
									'description' => $_POST['description'],
									'hostname'    => $_POST['hostname'],
									'ip'          => $_POST['ip'],
									'port'        => $_POST['port'],
									'admin_port'  => $_POST['admin_port'],
									'admin_pass'  => $_POST['admin_pass'],
									'token'       => $_POST['token'],
									'group'       => $_POST['group'],
									'status'      => $_POST['status'],
								]);
								if($update === true) {
									exit("节点添加成功！");
								} else {
									Header("HTTP/1.1 404 Not Found");
									exit("节点添加失败！{$update}");
								}
							} else {
								Header("HTTP/1.1 404 Not Found");
								exit("提交的数据不合法！{$valid}");
							}
						} else {
							exit("你没有足够的权限这么做");
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				case "deletenode":
					$um = new SakuraPanel\UserManager();
					$nm = new SakuraPanel\NodeManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						$us = $um->getInfoByUser($_SESSION['user']);
						if($us['group'] == "admin") {
							if(SakuraPanel\Regex::isValid($_POST, [
								"id" => SakuraPanel\Regex::TYPE_NUMBER
							]) === true) {
								$result = $nm->deleteNode($_POST['id']);
								if($result === true) {
									exit("节点删除成功！");
								} else {
									Header("HTTP/1.1 404 Not Found");
									exit("节点删除失败！{$result}");
								}
							} else {
								Header("HTTP/1.1 404 Not Found");
								exit("提交的数据不合法！{$valid}");
							}
						} else {
							exit("你没有足够的权限这么做");
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				case "updatebroadcast":
					$um = new SakuraPanel\UserManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						$us = $um->getInfoByUser($_SESSION['user']);
						if($us['group'] == "admin") {
							if(isset($_POST['data'])) {
								$result = SakuraPanel\Settings::set("broadcast", $_POST['data']);
								if($result === true) {
									exit("公告更新成功！");
								} else {
									exit("数据更新失败！{$result}");
								}
							} else {
								Header("HTTP/1.1 404 Not Found");
								exit("提交的数据不合法！");
							}
						} else {
							exit("你没有足够的权限这么做");
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				case "updatehelpinfo":
					$um = new SakuraPanel\UserManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						$us = $um->getInfoByUser($_SESSION['user']);
						if($us['group'] == "admin") {
							if(isset($_POST['data'])) {
								$result = SakuraPanel\Settings::set("helpinfo", $_POST['data']);
								if($result === true) {
									exit("帮助更新成功！");
								} else {
									exit("数据更新失败！{$result}");
								}
							} else {
								Header("HTTP/1.1 404 Not Found");
								exit("提交的数据不合法！");
							}
						} else {
							exit("你没有足够的权限这么做");
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				case "preview":
					$um = new SakuraPanel\UserManager();
					if($um->isLogged()) {
						SakuraPanel\Utils::checkCsrf();
						include(ROOT . "/core/Parsedown.php");
						$markdown = new Parsedown();
						$markdown->setSafeMode(true);
						$markdown->setBreaksEnabled(true);
						$markdown->setUrlsLinked(true);
						if(isset($_POST['data'])) {
							exit($markdown->text($_POST['data']));
						} else {
							Header("HTTP/1.1 404 Not Found");
							exit("提交的数据不合法！");
						}
					} else {
						exit("登录会话已超时，请重新登录");
					}
					break;
				default:
					Header("HTTP/1.1 404 Not Found");
					exit("Undefined action {$params['action']}");
			}
		}
	}
}