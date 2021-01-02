<?php

function out($success, $obj) {
    if ($obj === false) {
        return out(false, "unkown error occurred");
    }

    $res = array();
    if ($success) {
        $res = array("success" => true, "data" => $obj);
    } else {
        $res = array("success" => false);
        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $res[$key] = $value;
            }
        } else {
            $res["error"] = $obj;
        }
    }
    
    Debug::Log("Returning:", "Output");
    Debug::Log($res, "Output");
    $json = json_encode($res, JSON_PRETTY_PRINT);
    echo $json;
    exit(0);
}

?>