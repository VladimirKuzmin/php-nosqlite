<?php

namespace NoSQLite\Key;

use NoSQLite\Key\Base\FieldKey;

class String extends FieldKey {

    public function getFieldType() {
        return 'TEXT';
    }

    static public function serialize($val) {
        if (is_string($val)) {
            return (string)$val;
        }
        if (is_callable(array($val, '__toString'))) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $val->__toString();
        }
        return null;
    }
}
