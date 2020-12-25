<?php

class Companion {

    private $baseUrl = "http://localhost/nano/nano.php";
    private $compsVersion = "0.0.1";
    private $compsName = "service_companions";
    private $name;
    private $version;

    public function __construct($self) {
        $this->name = $self->shortName();
        $this->version = $self->version();
    }

    public function __destruct() {

    }

    public function call($name, $method, $args) {
        $versionResult = $this->request("$this->baseUrl/$this->compsName/$this->compsVersion/resolve/name:$this->name/version:$this->version/dep:$name");
        if ($versionResult['success'] == false || count($versionResult['result']) == 0) {
            return array("success" => false, "error" => "no such dependent companion found!");
        }

        $version = $versionResult['result'][0]['VersionString'];
        return $this->request("$this->baseUrl/$name/$version/$method", $args);
    }

    private function request($url, $data = array(), $method = 'GET') {
        Debug::Log("requesting '$url' with data: ", "Companion");
        Debug::Log($data, "Companion");

        $options = array (
            'http' => array (
                'header'  => "Content-Type:application/json",
                'method'  => $method,
                'content' => json_encode($data)
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false || $result == null) {
            Debug::Log("http error", "Companion");
            return array("success" => false, "error" => "http-error");
        }

        $out = json_decode($result, true);

        if ($out === false || $out == null) {
            Debug::Log("not json format, '$result'", "Companion");
            return array("success" => false, "error" => "not json format", "result" => $result);
        }

        Debug::Log("output is: $result", "Companion");
        Debug::Log($out, "Companion");

        return $out;
    }

}


?>