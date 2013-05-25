<?php

namespace NoSQLite\Key\Comparator;

use NoSQLite\Object;

$base_comparator = BaseComparator::getClass();
$like = Like::getClass();

class OperatorsRegistry extends Object {

    static protected $registry = array();

    static function register($name, $args) {
        self::$registry[$name] = $args;
    }

    static function get($name) {
        if (array_key_exists($name, self::$registry)) {
            return self::$registry[$name];
        }
        throw new \Exception('Unknown operator');
    }
}

$ops = array(
    # BaseComparator types
    'eq' => array('class' => $base_comparator, 'operator' => '='),
    'lt' => array('class' => $base_comparator, 'operator' => '<'),
    'lte' => array('class' => $base_comparator, 'operator' => '<='),
    'gt' => array('class' => $base_comparator, 'operator' => '>'),
    'gte' => array('class' => $base_comparator, 'operator' => '>='),
    'neq' => array('class' => $base_comparator, 'operator' => '<>'),

    # LIKE and modifications
    'like' => array('class' => $like, 'template' => null),
    'startswith' => array('class' => $like, 'template' => '%s%%'),
    'endswith' => array('class' => $like, 'template' => '%%%s'),
    'contains' => array('class' => $like, 'template' => '%%%s%%'),
);

foreach ($ops as $name => $args) {
    OperatorsRegistry::register($name, $args);
}
