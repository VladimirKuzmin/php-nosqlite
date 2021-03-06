<?php

namespace NoSQLite\Field;

use NoSQLite\Field\Base\Field;


class Raw extends Field {

    public function getFieldType() {
        return 'TEXT';
    }

    static public function serialize($val) {
        // TODO: use serializer from collection
        return serialize($val);
    }

    static public function __callStatic($name, $args) {
        if ($name != 'eq' || $name != 'not_eq') {
            throw new \Exception("This key supports only eq/not_eq operators");
        }
        return parent::__callStatic($name, $args);
    }
}
