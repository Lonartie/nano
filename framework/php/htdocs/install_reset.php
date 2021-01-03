<?php
// RESET / CREATE DB

$database_host = "localhost";
$database_user = "root";
$database_pass = "root";
$packages_path = "./../install_packages";
$packages_install_path = $_SERVER['DOCUMENT_ROOT'] . "/../services/";
$phpExec = "C:/xampp/php/php.exe";

out("connecting to database");
$connection = new mysqli($database_host, $database_user, $database_pass);

if (!$connection) {
    fail("connection failed!");
}

out("<br>creating datbase 'nano'");
$connection->query("DROP DATABASE nano");
$connection->query("CREATE DATABASE nano");
$connection->select_db("nano");

// create tables
out("&emsp;creating table 'service'");
$connection->query(
    "CREATE TABLE `service` (".
            "`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, ".
            "`Name` text NOT NULL".
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

out("&emsp;creating table 'service_companions'");
$connection->query(
    "CREATE TABLE `service_companions` (".
        "`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, ".
        "`SourceVersionID` int(11) NOT NULL, ".
        "`TargetVersionID` int(11) NOT NULL".
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

out("&emsp;creating table 'service_details'");
$connection->query(
    "CREATE TABLE `service_details` (".
        "`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, ".
        "`ServiceID` int(11) NOT NULL, ".
        "`CompleteName` text NOT NULL, ".
        "`Description` text NOT NULL, ".
        "`ServiceType` text NOT NULL".
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

out("&emsp;creating table 'service_methods'");
$connection->query(
    "CREATE TABLE `service_methods` (".
        "`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,".
        "`ServiceID` int(11) NOT NULL,".
        "`Name` text NOT NULL,".
        "`VersionID` int(11) NOT NULL".
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

out("&emsp;creating table 'service_method_parameters'");
$connection->query(
    "CREATE TABLE `service_method_parameters` (".
        "`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,".
        "`MethodID` int(11) NOT NULL,".
        "`ParameterName` text NOT NULL,".
        "`Optional` tinyint(1) NOT NULL".
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

out("&emsp;creating table 'service_versions'");
$connection->query(
    "CREATE TABLE `service_versions` (".
        "`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,".
        "`ServiceID` int(11) NOT NULL,".
        "`ReleaseDate` datetime NOT NULL DEFAULT current_timestamp(),".
        "`VersionString` text NOT NULL,".
        "`ReleaseNotes` longtext NOT NULL".
      ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

// install every base package
out("<br>installing packages");
remove($packages_install_path);
$files = scandir($packages_path);
foreach ($files as $file) {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if ($extension == "zip") {
        out("&emsp;installing '$file'");

        $temp = createTemp();
        unzipFile("$packages_path/$file", $temp);
        $information = getInformation("$temp/evaluation.php");
        $services = $information['services'];
        foreach ($services as $serviceName => $serviceObj) {
            $serviceVersion = $serviceObj['version'];

            out("&emsp;&emsp;found service '$serviceName -> $serviceVersion'");

            $package_path = "$packages_install_path/$serviceName/$serviceVersion";
            mkdir($package_path, 0777, true);

            out("&emsp;&emsp;copying service to server");
            xcopy($temp, $package_path);

            out("&emsp;&emsp;adding to database");

            addToDatabase($serviceObj);
        }
    }
}

out("<br>done");

function out($text) {
    echo $text . "<br>";
}

function fail($text) {
    out($text);
    exit(0);
}  

function remove($folder) {
    exec("RMDIR /S /Q \"$folder\"");
}

function createTemp($dir = null, $prefix = 'tmp_', $mode = 0700, $maxAttempts = 1000) {
    if (is_null($dir)) {
        $dir = sys_get_temp_dir();
    }

    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }

    if (strpbrk($prefix, '\\/:*?"<>|') !== false) {
        return false;
    }

    $attempts = 0;
    do {
        $path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
    } while (
        !mkdir($path, $mode) &&
        $attempts++ < $maxAttempts
    );

    return $path;
}

function unzipFile($zip, $path) {
    $zipper = new ZipArchive;

    if (!$zipper->open($zip)) {
        fail("could not unzip file!");
    }

    $zipper->extractTo($path);
    $zipper->close();

    return true;
}

function getInformation($file) {
    // execute
    global $phpExec;
    $cmd = "$phpExec -f \"$file\"";
    exec($cmd, $output);

    // parse output
    $result = json_decode(implode(" ", $output), true);
    
    // handle errors
    if ($result == null) {
        $out = implode(" ", $output);
        fail("failed to gather information");
    }

    if (count($result['services']) == 0) {
       fail("the files do not define any service!");
    }

    // output
    return $result;
}

function xcopy($sourceDir, $targetDir) {
    exec("xcopy \"$sourceDir\\*\" \"$targetDir\\*\"");
}

function addToDatabase($obj) {
    $name = $obj['shortName'];
    $longName = $obj['longName'];
    $description = $obj['description'];
    $version = $obj['version'];
    $methods = $obj['methods'];

    global $connection;

    $connection->query("INSERT INTO service (Name) VALUES ('$name')");
    $serviceID = $connection->insert_id;
    out("&emsp;&emsp;&emsp;added service '$name' with id '$serviceID'");

    $connection->query("INSERT INTO service_details (ServiceID, CompleteName, Description, ServiceType) VALUES ($serviceID, '$longName', '$description', 'PHP')");
    $detailsID = $connection->insert_id;
    out("&emsp;&emsp;&emsp;added service details '$longName', '$description' with id '$detailsID'");
    
    $connection->query("INSERT INTO service_versions (ServiceID, VersionString, ReleaseNotes) VALUES ($serviceID, '$version', 'base installation')");
    $versionID = $connection->insert_id;
    out("&emsp;&emsp;&emsp;added service version '$version' with id '$versionID'");

    foreach ($methods as $method) {
        $m_name = $method['name'];
        $m_params = $method['parameters'];
        out("&emsp;&emsp;&emsp;adding method '$m_name'");

        $connection->query("INSERT INTO service_methods (ServiceID, Name, VersionID) VALUES ($serviceID, '$m_name', $versionID)");
        $methodID = $connection->insert_id;
        out("&emsp;&emsp;&emsp;&emsp;added method '$m_name' with id '$methodID'");

        foreach ($m_params as $m_param) {
            $p_name = $m_param['name'];
            $p_opt = $m_param['optional'] ? 1 : 0;
            out("&emsp;&emsp;&emsp;&emsp;adding parameter '$p_name'");

            $connection->query("INSERT INTO service_method_parameters (MethodID, ParameterName, Optional) VALUES ($methodID, '$p_name', $p_opt)");
            $parameterID = $connection->insert_id;
            out("&emsp;&emsp;&emsp;&emsp;&emsp;added parameter '$p_name', '$p_opt' with id '$parameterID'");
        }
    }
}

?>