<?php

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
namespace NoSQLite\Key\Base;

use NoSQLite\Key\Comparator\OperatorsRegistry;
use NoSQLite\NotImplementedError;
use NoSQLite\Utils;

/**
 * Class FieldKey
 * @package NoSQLite\Key
 *
 * @method static \NoSQLite\Key\Comparator\BaseComparator eq comparator builder for '=' operator
 * @method static \NoSQLite\Key\Comparator\BaseComparator lt comparator builder for '<' operator
 * @method static \NoSQLite\Key\Comparator\BaseComparator lte comparator builder for '<=' operator
 * @method static \NoSQLite\Key\Comparator\BaseComparator gt comparator builder for '>' operator
 * @method static \NoSQLite\Key\Comparator\BaseComparator gte comparator builder for '>=' operator
 * @method static \NoSQLite\Key\Comparator\BaseComparator neq comparator builder for '<>' operator
 * @method static \NoSQLite\Key\Comparator\Like like comparator builder for 'LIKE' operator
 * @method static \NoSQLite\Key\Comparator\Like startswith comparator builder for "LIKE 'pattern%'" operator
 * @method static \NoSQLite\Key\Comparator\Like endswith comparator builder for "LIKE '%pattern'" operator
 * @method static \NoSQLite\Key\Comparator\Like contains comparator builder for "LIKE '%pattern%'" operator
 * @method static \NoSQLite\Key\Comparator\BaseComparator not_eq
 * @method static \NoSQLite\Key\Comparator\BaseComparator not_lt
 * @method static \NoSQLite\Key\Comparator\BaseComparator not_lte
 * @method static \NoSQLite\Key\Comparator\BaseComparator not_gt
 * @method static \NoSQLite\Key\Comparator\BaseComparator not_gte
 * @method static \NoSQLite\Key\Comparator\BaseComparator not_neq
 * @method static \NoSQLite\Key\Comparator\Like not_like
 * @method static \NoSQLite\Key\Comparator\Like not_startswith
 * @method static \NoSQLite\Key\Comparator\Like not_endswith
 * @method static \NoSQLite\Key\Comparator\Like not_contains
 */
abstract class FieldKey extends AbstractKey {

    protected $field;

    public function __construct() {
        $field = func_get_args();
        // can't dynamically instantiate class with arguments array, it's php
        if ((count($field) == 1) && (is_array($field[0]))) {
            $field = (array)$field[0];
        }
        $this->field = $field;
        $this->name = call_user_func_array(array($this, 'buildName'), $this->field);
    }

    public function getField() {
        return $this->field;
    }

    /**
     * @param $obj array
     * @return string
     */
    public function apply($obj) {
        $val = $obj;
        foreach ($this->field as $key) {
            if (!(is_array($val) || $val instanceof \ArrayObject) || !array_key_exists($key, $val)) {
                return null;
            }
            $val = $val[$key];
        }
        return static::serialize($val);
    }

    /**
     * @param $val mixed value to serialize
     * @throws \NoSQLite\NotImplementedError
     * @returns string
     */
    static public function serialize(/** @noinspection PhpUnusedParameterInspection */ $val) {
        throw new NotImplementedError();
    }

    static public function __callStatic($name, $args) {
        $not = false;
        if (substr($name, 0, 4) == 'not_') {
            $name = substr($name, 4);
            $not = true;
        }
        $op_args = OperatorsRegistry::get($name);
        if ($not) {
            $op_args['not'] = !Utils::array_get_key($op_args, 'not', false);;
        }
        return new $op_args['class'](static::getClass(), $args[0], $op_args);
    }
}
