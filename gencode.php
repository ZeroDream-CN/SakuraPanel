<?php
if(php_sapi_name() !== "cli") {
	exit("This program only can running on cli mode");
}
if(!file_exists("configuration.php")) {
	exit("未找到 configuration.php，请放在网站根目录运行\n");
}
function getRandomText($length) {
	$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
	$texts = "";
	for($i = 0;$i < $length;$i++) {
		$texts .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	return $texts;
}
include("configuration.php");
echo "请输入要生成的邀请码数量> ";
$num = trim(fgets(STDIN));
$conn = mysqli_connect($_config['db_host'], $_config['db_user'], $_config['db_pass'], $_config['db_name'], $_config['db_port']);
if(preg_match("/^[\d]{1,8}$/", $num)) {
	$num = Intval($num);
	for($i = 0;$i < $num;$i++) {
		$code = getRandomText(32);
		mysqli_query($conn, "INSERT INTO `invitecode` (`code`, `user`) VALUES ('{$code}', NULL)");
		echo "已添加：{$code}\n";
	}
	exit("已生成指定数量激活码至数据库！\n");
} else {
	exit("数量不合法\n");
}
