<?php

abstract class DebugType {
    const BrowserDebugger = 0;
    const Output = 1;
    const File = 2;
}

abstract class Constants {

    // Debugging
    const DebugEnabled = true;
    const DebugType = DebugType::File;
    const DebugNewLineCharacter = "\n";
    const DebugFilePath = "C:/xampp/core/logs/log.txt";
    const DebugOnlyLastRequest = true;
    const DebugFlatten = true;
    const DebugFileMaxLines = 1000;
}

?>