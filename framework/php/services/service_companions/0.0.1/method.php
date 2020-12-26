<?php
if (!class_exists('Method')) {

class Method {

    public $name;
    public $parameters = array();

    public function __construct($name, $params) {
        $this->name = $name;
        $this->parameters = $params;
    }

}
}

?>