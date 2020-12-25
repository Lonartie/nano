<?php
include_once(dirname(__FILE__).'/../core/debugging/debug.php');
include_once(dirname(__FILE__).'/../core/service/system.php');

Debug::Log("NANO INTERFACE", "Module");

header('Content-Type: application/json');
$system = new System();
$system->run();

?>