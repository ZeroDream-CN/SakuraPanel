<?php
namespace SakuraPanel;

use SakuraPanel;

SakuraPanel\Utils::checkCsrf();

unset($_SESSION['user']);
unset($_SESSION['mail']);
unset($_SESSION['token']);
?>
<script>location='?page=login';</script>