<?php

namespace NoSQLite\Query\State;


use NoSQLite\Collection\SimpleCollection;
use NoSQLite\Object;

class PossibleQuery extends Object implements IQueryState {

    private static $_instance;

    static function instance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function mergeWhere($w1, $w2) {
        foreach ($w2 as $k => $v) {
            if (array_key_exists($k, $w1)) {
                if (is_array($w1[$k]) && is_array($v)) {
                    $w1[$k] = self::mergeWhere($w1[$k], $v);
                } else {
                    if ($w1[$k] != $v) {
                        throw new ImpossibleQueryException();
                    }
                }
            } else {
                $w1[$k] = $v;
            }
        }
        return $w1;
    }

    public function execute($where, SimpleCollection $collection) {
        return $collection->find($where);
    }
}