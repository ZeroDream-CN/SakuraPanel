<?php
namespace SakuraPanel;

class Utils {
	
	const PANEL_VERSION = "1.0.0";
	
	public static function reCAPTCHA($response)
	{
		global $_config;
		$data = http_build_query(Array(
			'secret' => $_config['recaptcha']['sitetoken'],
			'response' => $response
		));
		$options = Array(
			'http' => Array(
				'method' => 'POST',
				'header' => 'Content-type:application/x-www-form-urlencoded',
				'content' => $data,
				'timeout' => 15 * 60
			)
		);
		$context = stream_context_create($options);
		$result = @file_get_contents('https://recaptcha.net/recaptcha/api/siteverify', false, $context);
		$json = json_decode($result, true);
		return $json ? $json['success'] : false;
	}
	
	public static function isHttps()
	{
		if (!isset($_SERVER['HTTPS'])) {
			return false;
		}
		if ($_SERVER['HTTPS'] === 1) {
			return true;
		} elseif ($_SERVER['HTTPS'] === 'on') {
			return true;
		} elseif ($_SERVER['SERVER_PORT'] == 443) {
			return true;
		} elseif ($_SERVER['REQUEST_SCHEME'] == "https") {
			return true;
		}
		return false;
	}
	
	public static function http($url, $post = '', $cookie = '', $headers = '', $returnHeader = 0)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
		curl_setopt($curl, CURLOPT_REFERER, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		if($post) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		if($cookie) {
			curl_setopt($curl, CURLOPT_COOKIE, $cookie);
		}
		if($headers) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if(curl_errno($curl)) {
			$httpCode = curl_error($curl);
		}
		curl_close($curl);
		return [
			'status' => $httpCode,
			'body'   => $data
		];
	}
	
	public static function getFormatTraffic($data)
	{
		if($data < 1024) {
			return $data . "B";
		} elseif($data < 1024 * 1024) {
			return round($data / 1024, 2) . "KB";
		} elseif($data < 1024 * 1024 * 1024) {
			return round($data / 1024 / 1024, 2) . "MB";
		} else {
			return round($data / 1024 / 1024 / 1024, 2) . "GB";
		}
	}
	
	public static function checkCsrf()
	{
		if(isset($_GET['csrf'], $_SESSION['token']) && $_GET['csrf'] === $_SESSION['token']) {
			return true;
		} else {
			ob_clean();
			Header("HTTP/1.1 403 Forbidden");
			exit("Invalid CSRF Token");
		}
	}
}