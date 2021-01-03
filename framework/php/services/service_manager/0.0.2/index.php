<?php

include_once 'factory.php';
include_once 'nanoservice.php';
include_once 'method.php';
include_once 'parameter.php';

if (!class_exists('Uploader')) {
class ServiceManager extends NanoService {

    private $servicesPath;
    
    public function shortName() { return "service_manager"; } 
    public function longName() { return "Service manager"; } 
    public function description() { return "managing services"; } 
    public function version() { return "0.0.2"; }
    public function methods() {
        return array (
            new Method("add", array (
                new Parameter("bin"),
            )),
            new Method("remove_version", array (
                new Parameter("name"),
                new Parameter("version"),
            )),
            new Method("remove_all", array (
                new Parameter("name")
            ))
        );
    }

    public function __construct() {
        $this->servicesPath = Constants::servicesRoot();
    }
    
    public function add($bin) {
        $temp = $this->createTemp();
        $file = $this->saveFile($bin, $temp);
        $folder = $this->unzipFile($file, $temp);

        $runner = RunnerFactory::getRunnerByFolder($folder);
        $information = $runner->getInformation($folder . "/" . $runner->mainFile());

        Debug::Log("adding service: ", "ServiceManager");
        Debug::Log($information, "ServiceManager");

        return $this->register($information, $folder);
    }
    
    public function remove_version($name, $version) {
        Debug::Log("removing '$name : $version'", "ServiceManager");

        $db = new ServiceDB();
        $type = $db->getServiceType($name);

        if ($type === false) {
            Debug::Log("service '$name' not found", "ServiceManager");
            throw new Exception("service '$name' not found");
        }

        $db->removeServiceVersion($name, $version);
        $this->remove($this->servicesPath . "$name/$version");

        return true;
    }

    public function remove_all($name) {
        Debug::Log("removing '$name' completely", "ServiceManager");
        
        $db = new ServiceDB();
        $type = $db->getServiceType($name);

        if ($type === false) {
            Debug::Log("service '$name' not found", "ServiceManager");
            throw new Exception("service '$name' not found");
        }
        $this->remove($this->servicesPath . "$name");
        
        return $db->removeService($name);
    }

    private function createTemp($dir = null, $prefix = 'tmp_', $mode = 0700, $maxAttempts = 1000) {
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

    private function saveFile($bin, $temp) {
        $name = uniqid("", true);
        $path = "$temp/$name.zip";

        Debug::Log("Saving binary to '$temp'", "ServiceManager");

        file_put_contents($path, base64_decode($bin));

        return $path;
    }

    private function unzipFile($zip, $path) {
        Debug::Log("unzipping '$zip' to '$path'", "ServiceManager");
        $zipper = new ZipArchive;
        $targetPath = $this->createTemp();

        if (!$zipper->open($zip))
            return false;

        $zipper->extractTo($targetPath);
        $zipper->close();

        return $targetPath;
    }

    private function getMethods($obj) {
        $mtds = $obj['methods'];
        $methods = array();

        for ($m = 0; $m < count($mtds); $m++) {
            $mtd = $mtds[$m];
            $mname = $mtd['name'];
            $pms = $mtd['parameters'];

            $params = array();
            for ($p = 0; $p < count($pms); $p++) {
                $pm = $pms[$p];
                $pname = $pm['name'];
                $opti = $pm['optional'];

                $params[$pname] = $opti;
            }

            $methods[$mname] = $params;            
        }

        return $methods;
    }

    private function register($information, $folder) {
        $services = $information['Result']['services'];
        foreach ($services as $serviceName => $serviceObj) {
            $serviceVersion = $serviceObj['version'];
            $target = $this->servicesPath . "$serviceName/$serviceVersion";

            $this->xcopy($folder, $target);

            $db = new ServiceDB();

            if (!$db->exists($serviceName, $serviceVersion)) {
                if ($db->exists($serviceName)) {
                    $db->updateService($serviceName, $serviceVersion, $this->getMethods($serviceObj));
                } else {
                    $longName = $serviceObj['longName'];
                    $description = $serviceObj['description'];
                    $db->createService($longName, $serviceName, $description, $serviceVersion, $this->getMethods($serviceObj), "PHP");
                }
            } else {
                throw new Exception("service with that version already exist '$serviceName : $serviceVersion'");
            }
        }

        return array("registered" => $information);
    }

    private function xcopy($sourceDir, $targetDir) {
        Debug::Log("Copying from '$sourceDir' to '$targetDir'", "ServiceManager");
        exec("xcopy \"$sourceDir\\*\" \"$targetDir\\*\"");
    }

    private function remove($folder) {
        Debug::Log("Removing folder '$folder'", "ServiceManager");
        exec("RMDIR /S /Q \"$folder\"");
    }
}

ServiceFactory::register(new ServiceManager());

}

?>