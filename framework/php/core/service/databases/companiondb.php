<?php
include_once(dirname(__FILE__).'/../debugging/debug.php');
include_once(dirname(__FILE__).'/servicedb.php');

Debug::Log("CompanionsDB", "Module");

class CompanionDB {

    private $m_db;

    public function __construct() {
        Debug::Log("Construction", "CompanionsDB");
        $this->m_db = new ServiceDB();
    }

    public function __destruct() {
        Debug::Log("Destruction", "CompanionsDB");
    }

    public function getCompanions($ShortName, $Version) {
        Debug::Log("Requesting companions for '$ShortName : $Version'", "CompanionsDB");
        $result = $this->m_db->getCompanions($ShortName, $Version);
        Debug::Log("Got: ", "CompanionsDB");
        Debug::Log($result, "CompanionsDB");
        return $result;
    }

    public function getCompanion($ShortName, $Version, $OtherShortName) {
        Debug::Log("Requesting companion for '$ShortName : $Version -> $OtherShortName'", "CompanionsDB");
        $result = $this->m_db->getCompanion($ShortName, $Version, $OtherShortName);
        Debug::Log("Got: ", "CompanionsDB");
        Debug::Log($result, "CompanionsDB");
        return $result;
    }

    public function addCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        Debug::Log("Requesting new companion for '$ShortName : $Version -> $OtherShortName : $OtherVersion'", "CompanionsDB");
        $result = $this->m_db->addCompanion($ShortName, $Version, $OtherShortName, $OtherVersion);
        Debug::Log("Got: ", "CompanionsDB");
        Debug::Log($result, "CompanionsDB");
        return $result;
    }

    public function updateCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        Debug::Log("Requesting update companion for '$ShortName : $Version -> $OtherShortName : $OtherVersion'", "CompanionsDB");
        $result = $this->m_db->updateCompanion($ShortName, $Version, $OtherShortName, $OtherVersion);
        Debug::Log("Got: ", "CompanionsDB");
        Debug::Log($result, "CompanionsDB");
        return $result;
    }

    public function removeCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        Debug::Log("Requesting remove companion for '$ShortName : $Version -> $OtherShortName : $OtherVersion'", "CompanionsDB");
        $result = $this->m_db->removeCompanion($ShortName, $Version, $OtherShortName, $OtherVersion);
        Debug::Log("Got: ", "CompanionsDB");
        Debug::Log($result, "CompanionsDB");
        return $result;
    }
}

?>