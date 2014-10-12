<?php

namespace NoSQLite\Field\Comparator;

use NoSQLite\Object;

$base_comparator = BaseComparator::class;
$like = Like::class;

class OperatorsRegistry extends Object {

    static protected $registry = [];

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

$ops = [
    # BaseComparator types
    'eq' => ['class' => $base_comparator, 'operator' => '='],
    'lt' => ['class' => $base_comparator, 'operator' => '<'],
    'lte' => ['class' => $base_comparator, 'operator' => '<='],
    'gt' => ['class' => $base_comparator, 'operator' => '>'],
    'gte' => ['class' => $base_comparator, 'operator' => '>='],
    'neq' => ['class' => $base_comparator, 'operator' => '<>'],

    # LIKE and modifications
    'like' => ['class' => $like, 'template' => null],
    'startswith' => ['class' => $like, 'template' => '%s%%'],
    'endswith' => ['class' => $like, 'template' => '%%%s'],
    'contains' => ['class' => $like, 'template' => '%%%s%%'],
];

foreach ($ops as $name => $args) {
    OperatorsRegistry::register($name, $args);
}
