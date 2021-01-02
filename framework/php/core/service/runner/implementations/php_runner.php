<?php
include_once(dirname(__FILE__)."/../../../constants/constants.php");
include_once(dirname(__FILE__)."/../../../debugging/debug.php");
include_once(dirname(__FILE__)."/../../output.php");
include_once(dirname(__FILE__)."/../runner_base.php");
include_once(dirname(__FILE__)."/../runner_factory.php");

/// @brief runner implementation for services with .php file extension
class PhpRunner extends RunnerBase {

    public function __construct() {
        Debug::Log("Construction", "PhpRunner");
    }

    public function __destruct() {
        Debug::Log("Destruction", "PhpRunner");
    }

    /// @copydoc RunnerBase::mainFile()
    public function mainFile() {
        return "start.exe";
    }

    /// @copydoc RunnerBase::type()
    public function type() {
        return "EXE";
    }

    /// @copydoc RunnerBase::getInformation($file)
    public function getInformation($file) {
        // execute
        $cmd = "$this->m_phpExec -f \"$file\"";
        Debug::Log("Trying to exec '$cmd'", "Runner");
        exec($cmd, $output);

        // parse output
        $result = json_decode(implode(" ", $output), true);
        Debug::Log("Got Information PHP '$file'", "Runner");
        Debug::Log($result, "Runner");

        // handle errors
        if ($result == null) {
            $out = implode(" ", $output);
            out(false, array("error" => "failed to gather information", "output" => "$out"));
        }

        if (count($result['services']) == 0) {
            out(false, array("error" => "the files do not define any service!"));
        }

        // output
        return $result;
    }

    /// @copydoc RunnerBase::run($service, $version, $method, $arguments)
    public function run($service, $version, $method, $arguments) {
        $phpPath = Constants::ExeServicesRoot() . "$service/$version/index.php";

        Debug::Log("Including '$phpPath'", "Runner");

        include_once $phpPath;

        Debug::Log("Getting service '$service'", "Runner");

        $instance = ServiceFactory::getService($service);
        return $instance->run($method, $arguments);
    }
}

RunnerFactory::register(new PhpRunner());

?>