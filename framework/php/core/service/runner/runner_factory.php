<?php

/// @brief register or create runners (Runner)
class RunnerFactory {

    /// @returns a singleton list of all registered runners
    private static function runners() {
        static $runners = array();
        return $runners;
    }

    /// @brief registers the given runner
    /// @param runner must be an instance of the runner to register
    public static function register($runner) {
        array_push(self::runners(), $runner);
    }

    /// @returns the appropriate runner to run the service inside the given folder
    /// @note returns null if no appropriate runner could be found
    /// @param folder a relative or absolute path to the folder where the service exists in
    public static function getRunnerByFolder($folder) {
        foreach (self::runners() as $index => $runner) {
            $file = $folder . "/" . $runner->mainFile();
            if (is_file($file)) {
                return $runner;
            }
        }

        return null;
    }

    /// @returns the appropriate runner to run the service for the given type
    /// @note returns null if no appropriate runner could be found
    /// @param type the type of the runner (must be same as BaseRunner::getType() implementation)
    public static function getRunnerByType($type) {
        foreach (self::runners() as $index => $runner) {
            if ($runner->getType() == $type) {
                return $runner;
            }
        }

        return null;
    }
}

?>