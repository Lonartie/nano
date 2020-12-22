<?php
include_once '../core/debugging/debug.php';
Debug::Log("ServiceDB", "Module");

class ServiceDB {

    private $username = 'root';
    private $password = 'root';
    private $host = 'localhost';
    private $database = 'nano';
    private $connection;

    public function __construct() {
        Debug::Log("Constructor", "ServiceDB");
        Debug::Log("Connecting to Database '$this->database'", "ServiceDB");
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
    }

    public function __destruct() {
        Debug::Log("Destructor", "ServiceDB");
        Debug::Log("Disconnecting from Database", "ServiceDB");
        $this->connection->close();
    }

    private function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    private function run($sql) {
        Debug::Log("Running SQL: '".$sql."'", "ServiceDB");
        $result = $this->toArray($this->connection->query($sql));
        Debug::Log("Result:", "ServiceDB");
        Debug::Log($result, "ServiceDB");

        return $result;
    }
    
    private function toArray($result) {
        if ($result === false) {
            return false;
        } else if ($result === true) {
            return true;
        }

        $out = array();

        while($row = mysqli_fetch_array($result)) {
            $nrow = array();
            foreach($row as $key => $value) {
                if (!is_numeric($key)) {
                    $nrow[$key] = $value;
                }
            }
            array_push($out, $nrow);
        }
        
        return $out;
    }

    /*  Example for $Methods definition
        $Methods = array(
            "getByID" => array(         // function name
                "ID" => false           // parameter name + optional true / false
            ),
            "getByName" => array(       // function name
                "shortName" => false,   // parameter name + optional true / false
                "completeName" => true  // parameter name + optional true / false
            )
        );
    */
    public function createService($CompleteName, $ShortName, $Description, $Version, $Methods) {
        $CompleteName = $this->escape($CompleteName);
        $ShortName = $this->escape($ShortName);
        $Description = $this->escape($Description);
        $Version = $this->escape($Version);

        $baseResult = $this->run("INSERT INTO service (Name) VALUES ('$ShortName')");
        if ($baseResult === false) return false;

        $id = strval($this->connection->insert_id);
        $detailsResult = $this->run("INSERT INTO service_details (ServiceID, CompleteName, Description) VALUES ($id, '$CompleteName', '$Description')");
        $versionResult = $this->run("INSERT INTO service_versions (ServiceID, VersionString) VALUES ($id, '$Version')");

        $methodResults = true;
        foreach ($Methods as $Mkey => $Mvalue) {
            $methodName = $this->escape($Mkey);
            $resultA = $this->run("INSERT INTO service_methods (ServiceID, Name) VALUES ($id, '$methodName')");
            $methodID = $this->connection->insert_id;

            $resultB = true;
            foreach ($Mvalue as $Pkey => $Pvalue) {
                $parameterName = $this->escape($Pkey);
                $parameterOptional = intval($this->escape(strval($Pvalue)));
                $paramResult = $this->run("INSERT INTO service_method_parameters (MethodID, ParameterName, Optional) VALUES ($methodID, '$parameterName', $parameterOptional)");

                $resultB = $resultB && ($paramResult !== false);
            }

            $methodResults = $methodResults && ($resultA !== false) && ($resultB !== false);
        }

        return ($detailsResult !== false) && ($versionResult !== false) && ($methodResults !== false);
    }

    public function updateService($ShortName, $Version, $ReleaseNotes) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        $ReleaseNotes = $this->escape($ReleaseNotes);

        $idResult = $this->run("SELECT ID FROM service WHERE Name='$ShortName'");
        if ($idResult === false || count($idResult) != 1) return false;
        $id = strval($idResult[0]['ID']);

        $updateResult = $this->run("INSERT INTO service_versions (ServiceID, VersionString, ReleaseNotes) VALUES ($id, '$Version', '$ReleaseNotes')");
        return $updateResult !== false;
    }

    public function all() {
        $list = $this->run("SELECT * FROM service s, service_details d WHERE s.ID = d.ID");
        for ($i = 0; $i < count($list); $i++) {
            $ID = $list[$i]['ID'];
            $list[$i]['Versions'] = $this->run("SELECT * FROM service_versions WHERE ID=$ID");

            for ($k = 0; $k < count($list[$i]['Versions']); $k++) {
                $Date = $list[$i]['Versions'][$k]['ReleaseDate'];
                $list[$i]['Versions'][$k]['Methods'] = $this->run("SELECT * FROM service_methods WHERE ServiceID=$ID AND ReleaseDate='$Date'");

                for ($j = 0; $j < count($list[$i]['Versions'][$k]['Methods']); $j++) {
                    $MID = $list[$i]['Versions'][$k]['Methods'][$j]['ID'];
                    $list[$i]['Versions'][$k]['Methods'][$j]['Parameters'] = $this->run("SELECT * FROM service_method_parameters WHERE MethodID=$MID");
                }
            }
        }
        return $list;
    }

    public function clear() {
        $this->run("TRUNCATE TABLE service");
        $this->run("TRUNCATE TABLE service_details");
        $this->run("TRUNCATE TABLE service_versions");
        $this->run("TRUNCATE TABLE service_methods");
        $this->run("TRUNCATE TABLE service_method_parameters");
    }
}

?>