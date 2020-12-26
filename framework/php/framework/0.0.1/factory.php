<?php
if (!class_exists('ServiceFactory')) {

class ServiceFactory {

    private static $m_list = null;

    public static function register($service) {
        if (ServiceFactory::$m_list === null) {
            ServiceFactory::$m_list = array();
        }

        ServiceFactory::$m_list[$service->shortName()] = $service;
    }

    public static function getService($name) {
        if (ServiceFactory::$m_list === null) {
            ServiceFactory::$m_list = array();
        }

        return ServiceFactory::$m_list[$name];
    }

    public static function definition() {
        if (ServiceFactory::$m_list === null) {
            ServiceFactory::$m_list = array();
        }
        
        $def = array();
        $services = array();

        foreach (ServiceFactory::$m_list as $name => $instance) {
            $service = array();
            $mtds = $instance->methods();

            $service['longName'] = $instance->longName();
            $service['shortName'] = $instance->shortName();
            $service['description'] = $instance->description();
            $service['version'] = $instance->version();
            
            $methods = array();
            for ($m = 0; $m < count($mtds); $m++) {
                $method = array();

                $mtd = $mtds[$m];
                $mname = $mtd->name;
                $params = $mtd->parameters;

                $parameters = array();
                for ($p = 0; $p < count($params); $p++) {
                    $parameter = array();

                    $param = $params[$p];
                    $pname = $param->name;
                    $opti = $param->optional;

                    $parameter['name'] = $pname;
                    $parameter['optional'] = $opti;

                    array_push($parameters, $parameter);
                }

                $method['name'] = $mname;
                $method['parameters'] = $parameters;
                array_push($methods, $method);
            }

            $service['methods'] = $methods;
            $services[$name] = $service;
        }

        $def['services'] = $services;
        $def['success'] = true;

        return $def;
    }
}
}
?>