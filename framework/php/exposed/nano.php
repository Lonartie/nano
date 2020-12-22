<?php
include_once '../core/debugging/debug.php';
Debug::Log("test", "Module");

include_once '../core/service/service.php';

$service = new Service();
$service->run();

?>