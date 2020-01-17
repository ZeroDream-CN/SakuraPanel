<?php
namespace SakuraPanel;

use SakuraPanel;

class Settings {
	
	public static function get($key, $value = "")
	{
		$rs = Database::querySingleLine("settings", Array("key" => $key));
		if($rs) {
			return $rs['value'] ?? $value;
		}
		return $value;
	}
	
	public static function set($key, $value = "")
	{
		$rs = Database::querySingleLine("settings", Array("key" => $key));
		if($rs) {
			return Database::update("settings", Array("value" => $value), Array("key" => $key));
		} else {
			return Database::insert("settings", Array("key" => $key, "value" => $value));
		}
	}
}