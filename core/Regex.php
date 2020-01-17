<?php
namespace SakuraPanel;

class Regex {
	
	const TYPE_NUMBER    = 0;
	const TYPE_USERNAME  = 1;
	const TYPE_PROXYNAME = 2;
	const TYPE_EMAIL     = 3;
	const TYPE_DOMAIN    = 4;
	const TYPE_LETTER    = 5;
	const TYPE_NOTEMPTY  = 6;
	const TYPE_IPV4      = 7;
	const TYPE_IPV6      = 8;
	const TYPE_HOSTNAME  = 9;
	const TYPE_IPV4_V6   = 10;
	
	public static function isNumber($data)
	{
		return preg_match("/^[0-9]+$/", $data) ? true : false;
	}
	
	public static function isProxyName($data)
	{
		return preg_match("/^[A-Za-z0-9\_]{3,15}$/", $data) ? true : false;
	}
	
	public static function isUserName($data)
	{
		return preg_match("/^[A-Za-z0-9\_\-]{3,32}$/", $data) ? true : false;
	}
	
	public static function isDomain($data)
	{
		return preg_match("/^(?=^.{3,255}$)[a-zA-Z0-9\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{0,62}(\.[a-zA-Z0-9\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{0,62})+$/", $data) ? true : false;
	}
	
	public static function isEmail($data)
	{
		return preg_match("/^\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,48}$/", $data) ? true : false;
	}
	
	public static function isLetter($data)
	{
		return preg_match("/^[A-Za-z0-9]+$/", $data) ? true : false;
	}
	
	public static function isIpv4($data)
	{
		return filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}
	
	public static function isIpv6($data)
	{
		return filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}
	
	public static function isHostName($data)
	{
		return (Regex::isIpv4($data) || Regex::isIpv6($data) || Regex::isDomain($data));
	}
	
	public static function isValid($var, $group)
	{
		$valid = true;
		foreach($group as $name => $type) {
			if(!isset($var[$name])) {
				return "Undefined member {$name}";
			} else {
				switch($type) {
					case (Regex::TYPE_NUMBER):
						$valid = Regex::isNumber($var[$name]);
						break;
					case (Regex::TYPE_USERNAME):
						$valid = Regex::isUserName($var[$name]);
						break;
					case (Regex::TYPE_EMAIL):
						$valid = Regex::isEmail($var[$name]);
						break;
					case (Regex::TYPE_PROXYNAME):
						$valid = Regex::isProxyName($var[$name]);
						break;
					case (Regex::TYPE_DOMAIN):
						$valid = Regex::isDomain($var[$name]);
						break;
					case (Regex::TYPE_LETTER):
						$valid = Regex::isLetter($var[$name]);
						break;
					case (Regex::TYPE_NOTEMPTY):
						$valid = (!empty($var[$name]));
						break;
					case (Regex::TYPE_IPV4):
						$valid = Regex::isIpv4($var[$name]);
						break;
					case (Regex::TYPE_IPV6):
						$valid = Regex::isIpv6($var[$name]);
						break;
					case (Regex::TYPE_IPV4_V6):
						$valid = (Regex::isIpv4($var[$name]) || Regex::isIpv6($var[$name]));
						break;
					case (Regex::TYPE_HOSTNAME):
						$valid = Regex::isHostName($var[$name]);
						break;
					default:
						return "Undefined type {$type}";
				}
				if(!$valid) {
					return "Not valid {$name}";
				}
			}
		}
		return $valid;
	}
}