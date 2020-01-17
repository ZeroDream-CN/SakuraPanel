<?php
namespace SakuraPanel;

class Pages {
	
	public function loadPage($name, $data = null)
	{
		if(file_exists(ROOT . "/pages/{$name}.php")) {
			include(ROOT . "/pages/{$name}.php");
		} else {
			include(ROOT . "/pages/404.php");
		}
	}
	
	public function loadModule($name, $data = null)
	{
		if(file_exists(ROOT . "/modules/{$name}.php")) {
			include(ROOT . "/modules/{$name}.php");
		} else {
			include(ROOT . "/modules/404.php");
		}
	}
}