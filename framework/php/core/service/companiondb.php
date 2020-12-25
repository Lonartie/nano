<?php
include_once '../core/debugging/debug.php';
Debug::Log("Companions", "Module");

include_once '../core/service/servicedb.php';

class CompanionDB {

    private $m_db;

    public function __construct() {
        $this->m_db = new ServiceDB();
    }

    public function getCompanions($ShortName, $Version) {
        Debug::Log("Requesting companions for '$ShortName : $Version'", "Companions");
        $result = $this->m_db->getCompanions($ShortName, $Version);
        Debug::Log("Got: ", "Companions");
        Debug::Log($result, "Companions");
        return $result;
    }

    public function getCompanion($ShortName, $Version, $OtherShortName) {
        Debug::Log("Requesting companion for '$ShortName : $Version -> $OtherShortName'", "Companions");
        $result = $this->m_db->getCompanion($ShortName, $Version, $OtherShortName);
        Debug::Log("Got: ", "Companions");
        Debug::Log($result, "Companions");
        return $result;
    }

    public function addCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        Debug::Log("Requesting new companion for '$ShortName : $Version -> $OtherShortName : $OtherVersion'", "Companions");
        $result = $this->m_db->addCompanion($ShortName, $Version, $OtherShortName, $OtherVersion);
        Debug::Log("Got: ", "Companions");
        Debug::Log($result, "Companions");
        return $result;
    }

    public function updateCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        Debug::Log("Requesting update companion for '$ShortName : $Version -> $OtherShortName : $OtherVersion'", "Companions");
        $result = $this->m_db->updateCompanion($ShortName, $Version, $OtherShortName, $OtherVersion);
        Debug::Log("Got: ", "Companions");
        Debug::Log($result, "Companions");
        return $result;
    }

    public function removeCompanion($ShortName, $Version, $OtherShortName, $OtherVersion) {
        Debug::Log("Requesting remove companion for '$ShortName : $Version -> $OtherShortName : $OtherVersion'", "Companions");
        $result = $this->m_db->removeCompanion($ShortName, $Version, $OtherShortName, $OtherVersion);
        Debug::Log("Got: ", "Companions");
        Debug::Log($result, "Companions");
        return $result;
    }
}

?>