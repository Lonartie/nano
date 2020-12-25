<?php
include_once '../core/debugging/debug.php';
Debug::Log("NANO INTERFACE", "Module");
header('Content-Type: application/json');

// include_once '../core/service/servicedb.php';
// $db = new ServiceDB();
// echo json_encode($db->removeService("php_math"));

include_once '../core/service/service.php';
$service = new Service();
$service->run();

?>