<?php

namespace NoSQLite;

class Object {

    public static function getClass() {
        return get_called_class();
    }

}

class NotImplementedError extends \Exception {}
