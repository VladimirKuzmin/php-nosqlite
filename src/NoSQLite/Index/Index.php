<?php

namespace NoSQLite\Index;


class Index {

    protected $keys;

    public function __construct() {
        $this->keys = func_get_args();
        if (!$this->keys) {
            throw new \Exception('Empty index isn\'t allowed');
        }
    }

    public function __toString() {
        return '['.implode(':', $this->keys).']';
    }

    public function getKeys() {
        return $this->keys;
    }

}
