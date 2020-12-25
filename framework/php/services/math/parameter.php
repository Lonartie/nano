<?php
if (!class_exists('Parameter')) {

class Parameter {

    public $name;
    public $optional;

    public function __construct($name, $optional = false) {
        $this->name = $name;
        $this->optional = $optional;
    } 

}
}
?>