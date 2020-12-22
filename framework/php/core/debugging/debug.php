<?php

abstract class DebugType {
    const BrowserDebugger = 0;
    const Output = 1;
}

class Debug {

    private static $m_enabled = false;
    private static $m_type = DebugType::Output;

    public static function EnableLog($boolean) {
        Debug::$m_enabled = $boolean;
    }

    public static function SetDebugType($debug_type) {
        Debug::$m_type = $debug_type;
    }

    public static function Log($obj = null, $label = null) {
        if (Debug::$m_enabled) {
            $message = json_encode($obj, JSON_PRETTY_PRINT);
            $label = "Debug" . ($label ? " ($label): " : ': ');

            if (Debug::$m_type == DebugType::BrowserDebugger) {
                echo "<script>console.log(\"$label\", $message);</script>";
            } else 

            if (Debug::$m_type == DebugType::Output) {
                echo $label . $message . "<br>";
            }
        }
    }
}

?>