<?php
include_once(dirname(__FILE__) . '/../constants/constants.php');

class Debug {

    private static $m_enabled = Constants::DebugEnabled;
    private static $m_type = Constants::DebugType;
    private static $m_break = Constants::DebugNewLineCharacter;
    private static $m_filePath = Constants::DebugFilePath;
    private static $m_inited = false;

    public static function EnableLog($boolean) {
        Debug::$m_enabled = $boolean;
    }

    public static function SetDebugType($debugType) {
        Debug::$m_type = $debugType;
    }

    public static function Log($obj = null, $label = null) {
        if (Debug::$m_enabled) {
            if (Debug::$m_type == DebugType::File) {                
                if (!is_dir(dirname(Debug::$m_filePath))) {
                    mkdir(dirname(Debug::$m_filePath), 0777, true);
                }

                if (!file_exists(Debug::$m_filePath)) {
                    touch(Debug::$m_filePath);
                }
            }

            if (!Debug::$m_inited && Debug::$m_type == DebugType::File && Constants::DebugOnlyLastRequest) {
                Debug::$m_inited = true;
                file_put_contents(Debug::$m_filePath, "");
            }

            $message = json_encode($obj, JSON_PRETTY_PRINT);
            $label = "Debug" . ($label ? " ($label): " : ': ');

            if (Debug::$m_type == DebugType::BrowserDebugger) {
                echo "<script>console.log(\"$label\", $message);</script>";
            } else 

            if (Debug::$m_type == DebugType::Output) {
                echo $label . $message . Debug::$m_break;
            } else

            if (Debug::$m_type == DebugType::File) {
                file_put_contents(Debug::$m_filePath, date("Y-m-d H-i-s") . " " . $label . ": " . $message . "\n", FILE_APPEND);

                if (Constants::DebugFlatten) {
                    Debug::Flatten();
                }
            }
        }
    }

    private static function Flatten() {
        $content = file_get_contents(Debug::$m_filePath);
        $content = str_replace("\r", "", $content);
        $lines = explode("\n", $content);
        
        while (count($lines) > Constants::DebugFileMaxLines) {
            array_shift($lines);
        }
        
        file_put_contents(Debug::$m_filePath, implode(PHP_EOL, $lines));
    }
}

?>