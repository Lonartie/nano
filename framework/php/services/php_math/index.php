<?php

include_once 'factory.php';
include_once 'nanoservice.php';
include_once 'method.php';
include_once 'parameter.php';
include_once 'companion.php';

class math extends NanoService {

    public function shortName() { return "php_math"; } 
    public function longName() { return "php_math lib"; } 
    public function description() { return "php_math service implementation"; } 
    public function version() { return "0.0.1"; }
    public function methods() 
    {
        return array(
            new Method("add", array(
                new Parameter("a", false), 
                new Parameter("b", true)
            )),
            new Method("sub", array(
                new Parameter("a", false), 
                new Parameter("b", true)
            )),
            new Method("mul", array(
                new Parameter("a", false), 
                new Parameter("b", true)
            )),
            new Method("div", array(
                new Parameter("a", false), 
                new Parameter("b", true)
            ))
        );
    }

    public function add($a, $b = 0) { return $a + $b; }
    public function sub($a, $b = 0) { return $a - $b; }
    public function mul($a, $b = 1) { return $a * $b; }
    public function div($a, $b = 1) { return $a / $b; }
}

ServiceFactory::register(new math());

?>