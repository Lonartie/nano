<?php

include_once(dirname(__FILE__) . "/../../debugging/debug.php");

class Database {

    private $username = 'root';
    private $password = 'root';
    private $host = 'localhost';
    private $database = 'nano';
    private $connection;

    public function __construct() {
        Debug::Log("Construction", "Database");
        Debug::Log("Connecting to Database '$this->database'", "Database");
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
    }
    
    public function __destruct() {
        Debug::Log("Destruction", "Database");
        Debug::Log("Disconnecting from Database", "Database");
        $this->connection->close();
    }

    public function run($sql) {        
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

    public function checkResult($queryResult) {
        if ($queryResult === true) {
            return true;
        }

        if ($queryResult === false || $queryResult == null || count($queryResult) == 0) {
            return false;
        }

        return true;
    }

}

?>