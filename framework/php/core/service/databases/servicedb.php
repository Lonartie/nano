<?php
include_once(dirname(__FILE__).'/../debugging/debug.php');

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

    private function checkResult($queryResult) {
        if ($queryResult === true) {
            return true;
        }

        if ($queryResult === false || $queryResult == null || count($queryResult) == 0) {
            return false;
        }

        return true;
    }


    // SERVICES

    public function serviceNames() {
        $serviceNamesResult = $this->run("SELECT s.Name, d.Description FROM service s, service_details d WHERE s.ID = d.ServiceID");
        if (!$this->checkResult($serviceNamesResult)) return array();
        $serviceNames = array();
        foreach ($serviceNamesResult as $index => $serviceName) {
            $serviceNames[$serviceName['Name']] = $serviceName['Description'];
        }
        return $serviceNames;
    }

    public function serviceVersions($service) {
        $service = $this->escape($service);
        $versionsResult = $this->run("SELECT v.VersionString, v.ReleaseDate FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$service'");
        if (!$this->checkResult($versionsResult)) {
            throw new Exception("Service '$service' not found!");
        }
        $versions = array();
        foreach ($versionsResult as $index => $version) {
            $versions[$version['VersionString']] = $version['ReleaseDate'];
        }
        return $versions;
    }

    public function serviceMethods($service, $version) {
        $service = $this->escape($service);
        $version = $this->escape($version);

        $versionIDResult = $this->run("SELECT v.ID FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$service' AND v.VersionString = '$version'");
        if (!$this->checkResult($versionIDResult)) {
            throw new Exception("Service '$service' and version '$version' not found!");
        }
        $versionID = $versionIDResult[0]['ID'];

        $methodResult = $this->run("SELECT ID, Name FROM service_methods WHERE VersionID = '$versionID'");
        if (!$this->checkResult($methodResult)) {
            throw new Exception("Service does not define any methods!");
        }

        $methods = array();
        foreach ($methodResult as $index => $method) {
            $methodID = $method['ID'];

            $parameterResult = $this->run("SELECT ParameterName FROM service_method_parameters WHERE MethodID = $methodID");
            if (!$this->checkResult($parameterResult)) {
                $parameterResult = array();
            }
            $parameters = array();
            foreach ($parameterResult as $index => $parameter) {
                array_push($parameters, $parameter['ParameterName']);
            }
            $methods[$method['Name']] = $parameters;
        }
        return $methods;
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
    public function createService($CompleteName, $ShortName, $Description, $Version, $Methods, $ServiceType) {
        $CompleteName = $this->escape($CompleteName);
        $ShortName = $this->escape($ShortName);
        $Description = $this->escape($Description);
        $Version = $this->escape($Version);
        $ServiceType = $this->escape($ServiceType);

        $baseResult = $this->run("INSERT INTO service (Name) VALUES ('$ShortName')");
        if ($baseResult === false) return false;

        $id = strval($this->connection->insert_id);
        $detailsResult = $this->run("INSERT INTO service_details (ServiceID, CompleteName, Description, ServiceType) VALUES ($id, '$CompleteName', '$Description', '$ServiceType')");
        $versionResult = $this->run("INSERT INTO service_versions (ServiceID, VersionString) VALUES ($id, '$Version')");
        $VersionID = $this->connection->insert_id;
        
        $methodResults = true;
        foreach ($Methods as $Mkey => $Mvalue) {
            $methodName = $this->escape($Mkey);
            $resultA = $this->run("INSERT INTO service_methods (ServiceID, Name, VersionID) VALUES ($id, '$methodName', $VersionID)");
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

    public function updateService($ShortName, $Version, $Methods, $ReleaseNotes = "") {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        $ReleaseNotes = $this->escape($ReleaseNotes);

        $id = $this->run("SELECT ID FROM service WHERE Name='$ShortName'")[0]['ID'];
        $res = $this->run("INSERT INTO service_versions (ServiceID, VersionString, ReleaseNotes) VALUES ($id, '$Version', '$ReleaseNotes')");
        $VersionID = $this->connection->insert_id;
        
        $methodResults = true;
        foreach ($Methods as $Mkey => $Mvalue) {
            $methodName = $this->escape($Mkey);
            $resultA = $this->run("INSERT INTO service_methods (ServiceID, Name, VersionID) VALUES ($id, '$methodName', $VersionID)");
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

        return ($res !== false) && ($methodResults !== false);
    }

    public function exists($ShortName, $Version = "") {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        if ($Version == "") return intval($this->run("SELECT COUNT(*) AS nums FROM service s WHERE name='$ShortName'")[0]['nums']) > 0;
        else return intval($this->run("SELECT COUNT(*) AS nums FROM service s, service_versions v WHERE s.ID = v.ServiceID AND v.VersionString='$Version' AND s.Name='$ShortName'")[0]['nums']) > 0;
    }

    public function all() {
        $list = $this->run("SELECT * FROM service s, service_details d WHERE s.ID = d.ID");
        for ($i = 0; $i < count($list); $i++) {
            $ID = $list[$i]['ID'];
            $list[$i]['Versions'] = $this->run("SELECT * FROM service_versions WHERE ID=$ID");

            for ($k = 0; $k < count($list[$i]['Versions']); $k++) {
                $VID = $list[$i]['Versions'][$k]['ID'];
                $list[$i]['Versions'][$k]['Methods'] = $this->run("SELECT * FROM service_methods WHERE ServiceID=$ID AND VersionID = $VID");

                for ($j = 0; $j < count($list[$i]['Versions'][$k]['Methods']); $j++) {
                    $MID = $list[$i]['Versions'][$k]['Methods'][$j]['ID'];
                    $list[$i]['Versions'][$k]['Methods'][$j]['Parameters'] = $this->run("SELECT * FROM service_method_parameters WHERE MethodID=$MID");
                }
            }
        }
        return $list;
    }

    public function getService($ShortName, $Version) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);

        return $this->run(
            "SELECT ".
                "s.Name, ".
                "d.CompleteName, ".
                "d.Description, ".
                "d.ServiceType, ".
                "v.VersionString, ".
                "v.ReleaseNotes, ".
                "v.ReleaseDate, ".
                "m.Name AS Method, ".
                "p.ParameterName, ".
                "p.Optional ".
            "FROM ".
                "service s, ".
                "service_details d, ".
                "service_versions v, ".
                "service_methods m, ".
                "service_method_parameters p ".
            "WHERE ".
                "d.ServiceID = s.ID AND ".
                "v.ServiceID = s.ID AND ".
                "m.ServiceID = s.ID AND ".
                "m.VersionID = v.ID AND ".
                "p.MethodID = m.ID AND ".
                "s.Name = '$ShortName' AND ".
                "v.VersionString = '$Version' "
        );
    }

    public function removeServiceVersion($name, $version) {
        $name = $this->escape($name);
        $version = $this->escape($version);

        $serviceIDResult = $this->run("SELECT ID FROM service WHERE Name = '$name'");
        if (!$this->checkResult($serviceIDResult)) return false;
        $serviceID = $serviceIDResult[0]['ID'];

        $serviceVersionIDResult = $this->run("SELECT ID FROM service_versions WHERE ServiceID = $serviceID AND VersionString = '$version'");
        if (!$this->checkResult($serviceVersionIDResult)) return false;
        $serviceVersionID = $serviceVersionIDResult[0]['ID'];

        $companionsRemoveResult = $this->run("DELETE FROM service_companions WHERE SourceVersionID = $serviceVersionID OR TargetVersionID = $serviceVersionID");
        if (!$this->checkResult($companionsRemoveResult)) return false;

        $serviceVersionRemoveResult = $this->run("DELETE FROM service_versions WHERE ServiceID = $serviceID AND VersionString = '$version'");
        if (!$this->checkResult($serviceVersionRemoveResult)) return false;

        $serviceMethodIDSResult = $this->run("SELECT ID FROM service_methods WHERE ServiceID = $serviceID AND VersionID = '$serviceVersionID'");
        if (!$this->checkResult($serviceMethodIDSResult)) return false;
        $serviceMethodIDS = array();
        for ($i = 0; $i < count($serviceMethodIDSResult); $i++) array_push($serviceMethodIDS, $serviceMethodIDSResult[$i]['ID']);

        foreach ($serviceMethodIDS as $index => $serviceMethodID) {
            $serviceMethodRemoveResult = $this->run("DELETE FROM service_methods WHERE ID = '$serviceMethodID'");
            if (!$this->checkResult($serviceMethodRemoveResult)) return false;

            $serviceMethodParameterRemoveResult = $this->run("DELETE FROM service_method_parameters WHERE MethodID = $serviceMethodID");
            if (!$this->checkResult($serviceMethodParameterRemoveResult)) return false;
        }

        return true;
    }

    public function getServiceVersions($name) {
        $name = $this->escape($name);

        $serviceIDResult = $this->run("SELECT ID FROM service WHERE Name = '$name'");
        if (!$this->checkResult($serviceIDResult)) return false;
        $serviceID = $serviceIDResult[0]['ID'];

        $serviceVersionsResult = $this->run("SELECT VersionString FROM service_versions WHERE ServiceID = $serviceID");
        if (!$this->checkResult($serviceVersionsResult)) return false;
        $serviceVersions = array();
        for ($i = 0; $i < count($serviceVersionsResult); $i++) array_push($serviceVersions, $serviceVersionsResult[$i]['VersionString']);

        return $serviceVersions;
    }

    public function removeService($name) {
        $versions = $this->getServiceVersions($name);

        if ($this->checkResult($versions)) {
            foreach ($versions as $index => $version) {
                if ($this->removeServiceVersion($name, $version) === false) {
                    return false;
                }
            }
        }

        $serviceIDResult = $this->run("SELECT ID FROM service WHERE Name = '$name'");
        if (!$this->checkResult($serviceIDResult)) return false;
        $serviceID = $serviceIDResult[0]['ID'];
        
        $serviceRemoveResult = $this->run("DELETE FROM service WHERE ID = $serviceID");
        if (!$this->checkResult($serviceRemoveResult)) return false;
        
        $serviceDetailsRemoveResult = $this->run("DELETE FROM service_details WHERE ServiceID = $serviceID");
        if (!$this->checkResult($serviceDetailsRemoveResult)) return false;

        return true;
    }

    public function getServiceType($name) {
        $name = $this->escape($name);
        $result = $this->run(
            "SELECT ".
                "d.ServiceType ".
            "FROM ".
                "service s, ".
                "service_details d ".
            "WHERE ".
                "s.ID = d.ServiceID AND ".
                "s.Name = '$name'"
        );
        
        if (!$this->checkResult($result)) return false;

        return $result[0]['ServiceType'];
    }

    // COMPANIONS

    public function getCompanions($ShortName, $Version) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        
        $result = $this->run(
            "SELECT ".
                "v.ID ".
            "FROM ".
                "service s, ".
                "service_versions v ".
            "WHERE ".
                "s.ID = v.ServiceID AND ".
                "s.Name = '$ShortName' AND ".
                "v.VersionString = '$Version'"
        );

        if (count($result) == 0) 
            return array();

        $versionID = $result[0]['ID'];

        return $this->run(
            "SELECT ".
                "s.Name, ".
                "v.VersionString ".
            "FROM ".
                "service s, ".
                "service_versions v, ".
                "service_companions c ".
            "WHERE ".
                "s.ID = v.ServiceID AND ".
                "c.TargetVersionID = v.ID AND ".
                "c.SourceVersionID = $versionID"
        );
    }

    public function getCompanion($ShortName, $Version, $OtherShortName) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        $OtherShortName = $this->escape($OtherShortName);
        
        $result = $this->run(
            "SELECT ".
                "v.ID ".
            "FROM ".
                "service s, ".
                "service_versions v ".
            "WHERE ".
                "s.ID = v.ServiceID AND ".
                "s.Name = '$ShortName' AND ".
                "v.VersionString = '$Version'"
        );

        if (count($result) == 0) 
            return array();

        $versionID = $result[0]['ID'];

        return $this->run(
            "SELECT ".
                "s.Name, ".
                "v.VersionString ".
            "FROM ".
                "service s, ".
                "service_versions v, ".
                "service_companions c ".
            "WHERE ".
                "s.ID = v.ServiceID AND ".
                "c.TargetVersionID = v.ID AND ".
                "c.SourceVersionID = $versionID AND ".
                "s.Name = '$OtherShortName'"
        );
    }

    public function addCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        $OtherShortName = $this->escape($OtherShortName);
        $OtherVersion = $this->escape($OtherVersion);
        
        $r2 = $this->run("SELECT v.ID FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$OtherShortName' AND v.VersionString = '$OtherVersion'");
        $r1 = $this->run("SELECT v.ID FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$ShortName' AND v.VersionString = '$Version'");
    
        if ($r1 === false || $r1 == null || count($r1) == 0) {
            return false;
        }
        if ($r2 === false || $r2 == null || count($r2) == 0) {
            return false;
        }
    
        $id1 = $r1[0]['ID'];
        $id2 = $r2[0]['ID'];

        return $this->run("INSERT INTO service_companions (SourceVersionID, TargetVersionID) VALUES ($id1, $id2)");
    }

    public function updateCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        $OtherShortName = $this->escape($OtherShortName);
        $OtherVersion = $this->escape($OtherVersion);
        
        $r1 = $this->run("SELECT v.ID FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$ShortName' AND v.VersionString = '$Version'");
        $r2 = $this->run("SELECT v.ID FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$OtherShortName' AND v.VersionString = '$OtherVersion'");

        if ($r1 === false || $r1 == null || count($r1) == 0) {
            return false;
        }
        if ($r2 === false || $r2 == null || count($r2) == 0) {
            return false;
        }
    
        $id1 = $r1[0]['ID'];
        $id2 = $r2[0]['ID'];

        $r3 = $this->run("SELECT v.ID FROM service s, service_versions v, service_companions c WHERE s.ID = v.ServiceID AND v.ID = c.TargetVersionID AND c.SourceVersionID = $id1 AND s.Name = '$OtherShortName'");

        if ($r3 === false || $r3 == null || count($r3) == 0) {
            return false;
        }

        $id2_old = $r3[0]['ID'];

        return $this->run(
            "UPDATE service_companions SET SourceVersionID = $id1, TargetVersionID = $id2 ".
            "WHERE SourceVersionID = $id1 AND TargetVersionID = $id2_old"
        );
        
    }

    public function removeCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        $ShortName = $this->escape($ShortName);
        $Version = $this->escape($Version);
        $OtherShortName = $this->escape($OtherShortName);
        $OtherVersion = $this->escape($OtherVersion);        
        
        $r1 = $this->run("SELECT v.ID FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$ShortName' AND v.VersionString = '$Version'");
        $r2 = $this->run("SELECT v.ID FROM service s, service_versions v WHERE s.ID = v.ServiceID AND s.Name = '$OtherShortName' AND v.VersionString = '$OtherVersion'");

        if ($r1 === false || $r1 == null || count($r1) == 0) {
            return false;
        }
        if ($r2 === false || $r2 == null || count($r2) == 0) {
            return false;
        }
    
        $id1 = $r1[0]['ID'];
        $id2 = $r2[0]['ID'];

        return $this->run("DELETE FROM service_companions WHERE SourceVersionID = $id1 AND TargetVersionID = $id2");
    }

    public function clear() {
        $this->run("TRUNCATE TABLE service");
        $this->run("TRUNCATE TABLE service_details");
        $this->run("TRUNCATE TABLE service_versions");
        $this->run("TRUNCATE TABLE service_methods");
        $this->run("TRUNCATE TABLE service_method_parameters");
        $this->run("TRUNCATE TABLE service_companions");
    }
}

?>