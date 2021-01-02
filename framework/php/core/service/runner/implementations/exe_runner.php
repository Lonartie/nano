<?php
include_once(dirname(__FILE__)."/../../../constants/constants.php");
include_once(dirname(__FILE__)."/../../../debugging/debug.php");
include_once(dirname(__FILE__)."/../../output.php");
include_once(dirname(__FILE__)."/../runner_base.php");
include_once(dirname(__FILE__)."/../runner_factory.php");

/// @brief runner implementation for services with .exe file extension
class ExeRunner extends RunnerBase {

    public function __construct() {
        Debug::Log("Construction", "ExeRunner");
    }

    public function __destruct() {
        Debug::Log("Destruction", "ExeRunner");
    }

    /// @copydoc RunnerBase::mainFile()
    public function mainFile() {
        return "start.exe";
    }

    /// @copydoc RunnerBase::type()
    public function type() {
        return "PHP";
    }

    /// @copydoc RunnerBase::getInformation($file)
    public function getInformation($file) {
        // execute
        Debug::Log("Trying to exec '$file'", "Runner");
        exec($file, $output);

        // parse output
        $result = json_decode(implode(" ", $output), true);
        Debug::Log("Got Information EXE '$folder'", "Runner");
        Debug::Log($result, "Runner");

        // handle errors
        if ($result == null) {
            $out = implode(" ", $output);
            out(false, array("error" => "failed to gather information", "output" => "$out"));
        }

        if (count($result['services']) == 0) {
            out(false, "the files do not define any service!");
        }

        // output
        return $result;
    }

    /// @copydoc RunnerBase::run($service, $version, $method, $arguments)
    public function run($service, $version, $method, $arguments) {
        array_unshift($arguments, $method);
        array_unshift($arguments, $service);

        for ($i = 0; $i < count($arguments); $i++) {
            $arguments[$i] = "\"".$arguments[$i]."\"";
        }

        $execPath = Constants::ExeServicesRoot() . "$service/$version/start.exe";

        $argsLine = implode(" ", $arguments);
        $cmd = "$execPath $argsLine";
        Debug::Log("Running cmd '$cmd'", "Runner");
        exec($cmd, $output);
        return json_decode(implode(" ", $output), true);
    }
}

RunnerFactory::register(new ExeRunner());

?>