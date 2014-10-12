<?php

namespace NoSQLite\Field;

use NoSQLite\Field\Base\Field;

class String extends Field {

    public function getFieldType() {
        return 'TEXT';
    }

    static public function serialize($val) {
        if (is_string($val)) {
            return (string)$val;
        }
        if (is_callable([$val, '__toString'])) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $val->__toString();
        }
        return null;
    }
}
