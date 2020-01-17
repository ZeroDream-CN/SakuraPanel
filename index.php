<?php
namespace SakuraPanel;

use SakuraPanel;

OB_START();
SESSION_START();

define("ROOT", __DIR__);

include(ROOT . "/configuration.php");
include(ROOT . "/core/Database.php");
include(ROOT . "/core/Regex.php");
include(ROOT . "/core/Utils.php");
include(ROOT . "/core/Settings.php");
include(ROOT . "/core/Smtp.php");

$conn = null;
$db = new SakuraPanel\Database();

include(ROOT . "/core/Pages.php");
include(ROOT . "/core/UserManager.php");
include(ROOT . "/core/NodeManager.php");
include(ROOT . "/core/ProxyManager.php");
include(ROOT . "/core/PostHandler.php");
include(ROOT . "/core/Router.php");