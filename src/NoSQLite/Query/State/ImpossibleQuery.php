<?php

namespace NoSQLite\Query\State;


use NoSQLite\Collection\SimpleCollection;
use NoSQLite\Object;

class ImpossibleQuery extends Object implements IQueryState {

    private static $_instance;

    static function instance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function mergeWhere($w1, $w2) {
        return null;
    }

    function execute($where, SimpleCollection $collection) {
        return array();
    }
}