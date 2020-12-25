<?php
include_once(dirname(__FILE__).'/../debugging/debug.php');
include_once(dirname(__FILE__).'/servicedb.php');
include_once(dirname(__FILE__).'/runner.php');
include_once(dirname(__FILE__).'/output.php');

Debug::Log("System", "Module");

class System {

    private $m_method;
    private $m_request;
    private $m_json;    
    private $m_db;

    public function __construct() {
        Debug::Log("Constructor", "System");
        $this->m_db = new ServiceDB();
        $this->m_method = $_SERVER['REQUEST_METHOD'];
        if (isset($_SERVER['PATH_INFO'])) $this->m_request = explode("/", substr($_SERVER['PATH_INFO'], 1));
        else $this->m_request = array();
        $this->m_json = file_get_contents('php://input');

        if (count($this->m_request) > 0) {
            if ($this->m_request[count($this->m_request) - 1] == '') {
                array_pop($this->m_request);
            }
        }
    }

    public function __destruct() {
        Debug::Log("Destructor", "System");
    }

    private function getArgs() {
        $args = array();
        
        // args from url
        for ($i = 3; $i < count($this->m_request); $i++) {
            $arg = $this->m_request[$i];
            if (!strpos($arg, ":") || count(explode(":", $arg)) != 2) {
                out(false, "argument '$arg' is ill formed -> 'url/service/version/method/parameter:value/parameter:value)");
            }
            $name = trim(explode(":", $arg)[0]);
            $value = trim(explode(":", $arg)[1]);
            $args[$name] = $value;
        }

        // args from json
        $data = json_decode($this->m_json);
        if ($data !== false && $data !== null) {
            foreach ($data as $key => $value) {
                $args[trim($key)] = trim(strval($value));
            }
        }

        return $args;
    }

    private function showServices() {
        out(true, array(
                "info" => "append a valid service name to the url", 
                "services" => $this->m_db->serviceNames()
            )
        );
    }

    private function showVersions() {
        out(true, array(
                "info" => "append a valid version to the url", 
                "versions" => array_reverse (
                    $this->m_db->serviceVersions(
                        $this->m_request[0]
                    )
                )
            )
        );
    }

    private function showMethods() {
        out(true, array(
                "info" => "append a valid method + parameters to the url. syntax: service/version/method/param1:value1/param2:value2",
                "methods" => $this->m_db->serviceMethods (
                    $this->m_request[0],
                    $this->m_request[1]
                )
            )
        );
    }

    private function getInformation() {
        if (count($this->m_request) == 0) {
            $this->showServices();
        } else if (count($this->m_request) == 1) {
            $this->showVersions();
        } else if (count($this->m_request) == 2) {
            $this->showMethods();
        }

        $name = $this->m_request[0];
        $version = $this->m_request[1];
        $method = $this->m_request[2];

        $services = $this->m_db->getService($name, $version);
        
        if ($services === false || count($services) == 0) {
            throw new Exception("service '$name' not found!");
        }

        $result = array();
        $parameters = array();

        $methodFound = false;
        for ($i = 0; $i < count($services); $i++) {
            $service = $services[$i];

            if ($service['Name'] == $name && $service['VersionString'] == $version && $service['Method'] == $method) {
                $methodFound = true;
                $result['Name'] = $service['Name'];
                $result['Version'] = $service['VersionString'];
                $result['Method'] = $service['Method'];
                $result['Description'] = $service['Description'];
                $result['ReleaseDate'] = $service['ReleaseDate'];
                $result['ReleaseNotes'] = $service['ReleaseNotes'];
                $result['ServiceType'] = $service['ServiceType'];
                $parameters[$service['ParameterName']] = $service['Optional'];
            }
        }

        if (!$methodFound) {
            throw new Exception("method '$method' for service '$name' not found!");
        }

        $result['Parameters'] = $parameters;
        return $result;
    }

    public function run() {
        try {
            Debug::Log("Run Requested with", "System");
            Debug::Log("Method:  " . $this->m_method, "System");
            Debug::Log("Request: " . implode(" | ", $this->m_request), "System");
            Debug::Log("JSON:    " . $this->m_json, "System");
            
            $information = $this->getInformation();

            Debug::Log("Got information: ", "System");
            Debug::Log($information, "System");

            if ($information !== false) {
                $runner = new Runner();
                $result = $runner->run($information, $this->getArgs());
                if ($result === false) {
                    return out(false, array("error" => "could not run service!"));
                }
                return out(true, $result);
            }
            
            Debug::Log("No matching service found!", "System");
            return out(false, array("error" => "no matching service found!", "reqest" => $this->m_request, "method" => $this->m_method));
        } catch (Exception $ex) {
            return out(false, $ex->getMessage());
        }
    }
}

?>