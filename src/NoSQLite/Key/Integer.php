<?php

namespace NoSQLite\Key;

use NoSQLite\Key\Base\FieldKey;

class Integer extends FieldKey {

    public function getFieldType() {
        return 'INTEGER';
    }

    static public function serialize($val) {
        if (filter_var($val, FILTER_VALIDATE_INT)) {
            return (int)$val;
        }
        return null;
    }
}
