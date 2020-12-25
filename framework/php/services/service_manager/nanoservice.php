<?php
if (!class_exists('NanoService')) {

abstract class NanoService {

    abstract public function shortName();
    abstract public function longName();
    abstract public function description();
    abstract public function version();
    abstract public function methods();

    public function run($methodName, $arguments) {
        $mtds = $this->methods();

        $methodFound = false;
        for ($i = 0; $i < count($mtds); $i++) {
            $method = $mtds[$i];
            $params = $method->parameters;

            if ($method->name == $methodName) {
                $methodFound = true;
                break;
            }
        }

        if (!$methodFound) {
            echo json_encode(array("success" => false, "error" => "method '$methodName' not found!"));
            exit(0);
        }

        try
        {
            $result = call_user_func_array(array($this, $methodName), $arguments);
            return array("success" => true, "result" => $result);
        } catch (Exception $ex) {
            return array("success" => false, "error" => $ex->getMessage());
        }
    }
}
}

?>