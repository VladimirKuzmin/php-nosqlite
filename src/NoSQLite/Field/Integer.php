<?php

namespace NoSQLite\Field;

use NoSQLite\Field\Base\Field;

class Integer extends Field {

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
