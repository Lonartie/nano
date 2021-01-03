<?php

abstract class DebugType {
    const BrowserDebugger = 0;                             // write to debugger of calling browser (using js-tech) (may collide with std output)
    const Output = 1;                                      // write to the std output (will collide with std output)
    const File = 2;                                        // write to a file (won't collide with std output)
}

abstract class Constants {

    // Debugging
    const DebugEnabled = true;                             // enabling / disabling debug output
    const DebugType = DebugType::File;                     // where to write the debug data
    const DebugNewLineCharacter = "\n";                    // which character to use for 'new-lines' (file mode uses '\n' fixed)
    const DebugFilePath = "C:/xampp/core/logs/log.txt";    // where to write the debug data (in case of file mode)
    const DebugOnlyLastRequest = false;                    // whether or not to only save the latest call (in case of file mode)
    const DebugFlatten = true;                             // whether or not to limit the number of lines in log output (oldest output gets deleted first) (in case of file mode)
    const DebugFileMaxLines = 1000;                        // how many lines to save when 'DebugFlatten' is active

    // Runners
    static function servicesRoot() {                       // where to store services
        return $_SERVER['DOCUMENT_ROOT'] . "/../services/";
    }

}

?>