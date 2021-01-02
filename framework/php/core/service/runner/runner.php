<?php
include_once(dirname(__FILE__).'/../../debugging/debug.php');
include_once(dirname(__FILE__).'/../companiondb.php');
include_once(dirname(__FILE__).'/../output.php');
include_once(dirname(__FILE__).'/runner_factory.php');

Debug::Log("Runner", "Module");

class Runner {

    private $m_phpExec = "C:/xampp/php/php.exe";

    public function __construct() {
        Debug::Log("Constructor", "Runner");
    }

    public function __destruct() {
        Debug::Log("Destructor", "Runner");
    }

    private function convertArgs($information, $arguments) {
        $args = array();

        foreach ($information['Parameters'] as $name => $optional) {
            $found = false;
            $val = null;

            foreach ($arguments as $arg => $value) {
                if ($name == $arg) {
                    $found = true;
                    $val = $value;
                }
            }

            if (!$found && !$optional) {
                out(false, "non-optional parameter missing '$name'");
            }
            
            if ($found) {
                array_push($args, $val);
            }
        }

        return $args;
    }

    public function getInformation($folder) {
        $runner = RunnerFactory::getRunnerByFolder($folder);

        if ($runner == null) {
            throw new Exception("Could not determine correct runner for '$folder'");
        }

        Debug::Log("Determined '".get_class($runner)."' for '$folder'", "Runner");
        return $runner->getInformation($folder . "/" . $runner->mainFile());
    }

    public function run($information, $arguments) {
        $type = $information['ServiceType'];

        $service = $information['Name'];
        $version = $information['Version'];
        $method = $information['Method'];

        Debug::Log("Got arguments:", "Runner");
        Debug::Log($arguments, "Runner");
        
        $runner = RunnerFactory::getByType($type);

        if ($runner == null) {
            throw new Exception("No runner registered for type '$type'");
        }

        $runner->run($service, $version, $method, $this->convertArgs($arguments));
    }
}

?>