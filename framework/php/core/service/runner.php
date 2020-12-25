<?php
include_once(dirname(__FILE__).'/../debugging/debug.php');
include_once(dirname(__FILE__).'/companiondb.php');
include_once(dirname(__FILE__).'/output.php');

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

    private function getInformationEXE($folder) {
        $cmd = "$folder/start.exe";
        Debug::Log("Trying to exec '$cmd'", "Runner");
        exec($cmd, $output);

        $result = json_decode(implode(" ", $output), true);
        Debug::Log("Got Information EXE '$folder'", "Runner");
        Debug::Log($result, "Runner");

        if ($result == null) {
            $out = implode(" ", $output);
            out(false, array("error" => "failed to gather information", "output" => "$out"));
        }

        if (count($result['services']) == 0) {
            out(false, "the files do not define any service!");
        }

        return $result;
    }

    private function getInformationPHP($folder) {
        $cmd = "$this->m_phpExec -f \"$folder/evaluation.php\"";
        Debug::Log("Trying to exec '$cmd'", "Runner");
        exec($cmd, $output);

        $result = json_decode(implode(" ", $output), true);
        Debug::Log("Got Information PHP '$folder'", "Runner");
        Debug::Log($result, "Runner");

        if ($result == null) {
            $out = implode(" ", $output);
            out(false, array("error" => "failed to gather information", "output" => "$out"));
        }

        if (count($result['services']) == 0) {
            out(false, array("error" => "the files do not define any service!"));
        }

        return $result;
    }

    public function getInformation($folder) {
        $information = array();

        $EXE = file_exists("$folder/start.exe");
        $PHP = file_exists("$folder/index.php");

        if ($EXE) {
            $information['Type'] = "EXE";
            $information['Result'] = $this->getInformationEXE($folder);
        } else

        if ($PHP) {
            $information['Type'] = "PHP";
            $information['Result'] = $this->getInformationPHP($folder);
        }

        return $information;
    }

    private function runEXE($information, $arguments) {
        $service = $information['Name'];
        $version = $information['Version'];
        $method = $information['Method'];

        $args = $this->convertArgs($information, $arguments);
        if ($method) array_unshift($args, $method);
        if ($service) array_unshift($args, $service);

        for ($i = 0; $i < count($args); $i++) {
            $args[$i] = "\"".$args[$i]."\"";
        }

        $execPath = $_SERVER['DOCUMENT_ROOT'] . "/../services/EXE/$service/$version/start.exe";

        $argsLine = implode(" ", $args);
        $cmd = "$execPath $argsLine";
        Debug::Log("Running cmd '$cmd'", "Runner");
        exec($cmd, $output);
        return json_decode(implode(" ", $output), true);
    }

    private function runPHP($information, $arguments) {
        $service = $information['Name'];
        $version = $information['Version'];
        $method = $information['Method'];

        $phpPath = $_SERVER['DOCUMENT_ROOT'] . "/../services/PHP/$service/$version/index.php";

        $args = $this->convertArgs($information, $arguments);

        Debug::Log("Including '$phpPath'", "Runner");

        include_once $phpPath;

        Debug::Log("Getting service '$service'", "Runner");

        $instance = ServiceFactory::getService($service);
        return $instance->run($method, $args);
    }

    public function run($information, $arguments) {
        $type = $information['ServiceType'];

        Debug::Log("Got arguments:", "Runner");
        Debug::Log($arguments, "Runner");

        if ($type == 'EXE') {
            return $this->runEXE($information, $arguments);
        } else

        if ($type == "PHP") {
            return $this->runPHP($information, $arguments);
        }
    }
}

?>