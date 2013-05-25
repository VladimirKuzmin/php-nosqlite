<?php

namespace NoSQLite;


class Utils {

    static function array_get_key($array, $key, $default=null) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }

} 
