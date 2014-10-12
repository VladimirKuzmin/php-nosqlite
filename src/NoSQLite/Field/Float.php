<?php

namespace NoSQLite\Field;

use NoSQLite\Field\Base\Field;

class Float extends Field {

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
