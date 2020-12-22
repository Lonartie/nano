<?php
include_once '../core/debugging/debug.php';
Debug::Log("Service", "Module");

include_once 'servicedb.php';

class Service {

    private $m_method;
    private $m_request;
    private $m_json;    
    private $m_db;

    public function __construct() {
        Debug::Log("Constructor", "Service");
        $this->m_db = new ServiceDB();
        $this->m_method = $_SERVER['REQUEST_METHOD'];
        $this->m_request = explode("/", substr($_SERVER['PATH_INFO'], 1));
        $this->m_json = file_get_contents('php://input');
    }

    public function __destruct() {
        Debug::Log("Destructor", "Service");
    }

    private function out($success, $obj) {
        $res = array("success" => $success, "data" => $obj);
        
        Debug::Log("Returning:", "Service");
        Debug::Log($res, "Service");

        $json = json_encode($res, JSON_PRETTY_PRINT);
        echo $json;
        return true;
    }

    public function run() {
        Debug::Log("Run Requested with", "Service");
        Debug::Log("Method:  " . $this->m_method, "Service");
        Debug::Log("Request: " . implode("/", $this->m_request), "Service");
        Debug::Log("JSON:    " . $this->m_json, "Service");

        $rq = $this->m_request;

        if ($this->m_method == 'GET' && count($rq) == 2 && $rq[0] == 'services' && $rq[1] == "all")
        {
            Debug::Log("Showing all services", "Service");
            $result = $this->m_db->all();
            return $this->out(true, $result);
        }

        Debug::Log("No mathing service found!");
        return $this->out(false, array("error" => "no mathing service found!", "reqest" => $this->m_request, "method" => $this->m_method));
    }
}

?>