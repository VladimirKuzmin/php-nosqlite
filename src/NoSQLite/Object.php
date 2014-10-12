<?php

namespace NoSQLite;

class Object {
    /** @deprecated use class property (php >=5.5) */
    public static function getClass() {
        return get_called_class();
    }
}

class NotImplementedError extends \Exception {}
