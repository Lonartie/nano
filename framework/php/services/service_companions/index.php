<?php

include_once 'factory.php';
include_once 'nanoservice.php';
include_once 'method.php';
include_once 'parameter.php';

class ServiceCompanions extends NanoService {
    
    public function shortName() { return "service_companions"; } 
    public function longName() { return "Service companions"; } 
    public function description() { return "Manages companion relations"; } 
    public function version() { return "0.0.1"; }
    public function methods() {
        return array(
            new Method("resolve", array(
                new Parameter("name", false),
                new Parameter("version", false),
                new Parameter("dep", false),
            )),
            new Method("allof", array(
                new Parameter("name", false),
                new Parameter("version", false)
            )),
            new Method("add", array(
                new Parameter("name", false),
                new Parameter("version", false),
                new Parameter("dep", false),
                new Parameter("depversion", false),
            )),
            new Method("remove", array(
                new Parameter("name", false),
                new Parameter("version", false),
                new Parameter("dep", false),
                new Parameter("depversion", false),
            )),
        );
    }

    public function resolve($name, $version, $dep) {
        $comp = new CompanionDB();
        return $comp->getCompanion($name, $version, $dep);
    }

    public function allof($name, $version) {
        $comp = new CompanionDB();
        return $comp->getCompanions($name, $version);
    }

    public function add($name, $version, $dep, $depversion) {
        $comp = new CompanionDB();
        $current = $comp->getCompanion($name, $version, $dep);
        $result;
        if (count($current) == 0) {
            $result = $comp->addCompanion($name, $version, $dep, $depversion);
        } else {
            $result = $comp->updateCompanion($name, $version, $dep, $depversion);
        }
        return $result;
    }

    public function remove($name, $version, $dep, $depversion) {
        $comp = new CompanionDB();
        $result = $comp->removeCompanion($name, $version, $dep, $depversion);
        return $result;
    }
}

ServiceFactory::register(new ServiceCompanions());

?>