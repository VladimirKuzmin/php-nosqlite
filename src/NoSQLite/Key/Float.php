<?php

namespace NoSQLite\Key;

use NoSQLite\Key\Base\FieldKey;

class Float extends FieldKey {

    public function getFieldType() {
        return 'REAL';
    }

    static public function serialize($val) {
        if (filter_var($val, FILTER_VALIDATE_FLOAT)) {
            return (int)$val;
        }
        return null;
    }
}
