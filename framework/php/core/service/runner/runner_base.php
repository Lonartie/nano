<?php

// include all implementations here!

include_once(dirname(__FILE__)."/php_runner.php");
include_once(dirname(__FILE__)."/exe_runner.php");

/// @brief base class for all types of runners.
///        a runner executes or retrieves information from a service.
///        a runner is defined (and later selected) by the mainFile.
///        for each file extensions there should only be 1 (one) runner.
abstract class RunnerBase {

    /// @returns the main file to search for 
    /// @note the main file is the service entry point of execution
    public abstract function mainFile();

    /// @returns the type of the file (typically the file extension)
    /// @note must be unique!
    public abstract function type();

    /// @returns a list of all services / methods / parameters the service registers
    /// @note the term service is used for a binary that contains services and each service inside a binary.
    ///       a binary may register multple services!
    /// @param file the entry point of that service
    public abstract function getInformation($file);

    /// @brief executes the the service by the given entry point
    /// @param service the name of the service to run
    /// @param version the version of the service to run
    /// @param method the method of the service to run
    /// @param arguments the arguments to run the method with
    /// @returns the result of that service
    public abstract function run($service, $version, $method, $arguments);
}

?>