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

    public function createService($CompleteName, $ShortName, $Description, $Url, $Exe, $Version) {
        $CompleteName = $this->escape($CompleteName);
        $ShortName = $this->escape($ShortName);
        $Description = $this->escape($Description);
        $Url = $this->escape($Url);
        $Exe = $this->escape($Exe);
        $Version = $this->escape($Version);

        $baseResult = $this->run("INSERT INTO service (Name) VALUES ('$ShortName')");
        if ($baseResult === false) return false;

        $id = strval($this->connection->insert_id);
        $detailsResult = $this->run("INSERT INTO service_details (ID, CompleteName, ShortName, Description, UrlPath, ExePath) VALUES ($id, '$CompleteName', '$ShortName', '$Description', '$Url', '$Exe')");
        $versionResult = $this->run("INSERT INTO service_versions (ID, VersionString) VALUES ($id, '$Version')");
        return $detailsResult !== false && $versionResult !== false;
    }

    public function updateService($ShortName, $Version, $ReleaseNotes) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        $ReleaseNotes = $this->escape($ReleaseNotes);

        $idResult = $this->run("SELECT ID FROM service WHERE Name='$ShortName'");
        if ($idResult === false || count($idResult) != 1) return false;
        $id = strval($idResult[0]['ID']);

        $updateResult = $this->run("INSERT INTO service_versions (ID, VersionString, ReleaseNotes) VALUES ($id, '$Version', '$ReleaseNotes')");
        return $updateResult !== false;
    }

    public function all() {
        $list = $this->run("SELECT * FROM service s, service_details d WHERE s.ID = d.ID");
        for ($i = 0; $i < count($list); $i++) {
            $ID = $list[$i]['ID'];
            $list[$i]['Versions'] = $this->run("SELECT VersionString, ReleaseDate, ReleaseNotes FROM service_versions WHERE ID=$ID");
        }
        return $list;
    }
}

?>