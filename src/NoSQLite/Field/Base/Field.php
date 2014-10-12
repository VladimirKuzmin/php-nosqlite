<?php

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
namespace NoSQLite\Field\Base;

use NoSQLite\Field\Comparator\OperatorsRegistry;
use NoSQLite\NotImplementedError;
use NoSQLite\Utils;

/**
 * Class FieldKey
 * @package NoSQLite\Field
 *
 * @method static \NoSQLite\Field\Comparator\BaseComparator eq comparator builder for '=' operator
 * @method static \NoSQLite\Field\Comparator\BaseComparator lt comparator builder for '<' operator
 * @method static \NoSQLite\Field\Comparator\BaseComparator lte comparator builder for '<=' operator
 * @method static \NoSQLite\Field\Comparator\BaseComparator gt comparator builder for '>' operator
 * @method static \NoSQLite\Field\Comparator\BaseComparator gte comparator builder for '>=' operator
 * @method static \NoSQLite\Field\Comparator\BaseComparator neq comparator builder for '<>' operator
 * @method static \NoSQLite\Field\Comparator\Like like comparator builder for 'LIKE' operator
 * @method static \NoSQLite\Field\Comparator\Like startswith comparator builder for "LIKE 'pattern%'" operator
 * @method static \NoSQLite\Field\Comparator\Like endswith comparator builder for "LIKE '%pattern'" operator
 * @method static \NoSQLite\Field\Comparator\Like contains comparator builder for "LIKE '%pattern%'" operator
 * @method static \NoSQLite\Field\Comparator\BaseComparator not_eq
 * @method static \NoSQLite\Field\Comparator\BaseComparator not_lt
 * @method static \NoSQLite\Field\Comparator\BaseComparator not_lte
 * @method static \NoSQLite\Field\Comparator\BaseComparator not_gt
 * @method static \NoSQLite\Field\Comparator\BaseComparator not_gte
 * @method static \NoSQLite\Field\Comparator\BaseComparator not_neq
 * @method static \NoSQLite\Field\Comparator\Like not_like
 * @method static \NoSQLite\Field\Comparator\Like not_startswith
 * @method static \NoSQLite\Field\Comparator\Like not_endswith
 * @method static \NoSQLite\Field\Comparator\Like not_contains
 */
abstract class Field extends AbstractField {

    protected $field;

    public function __construct() {
        $field = func_get_args();
        // can't dynamically instantiate class with arguments array, it's php
        if ((count($field) == 1) && (is_array($field[0]))) {
            $field = (array)$field[0];
        }
        $this->field = $field;
        $this->name = call_user_func_array([$this, 'buildName'], $this->field);
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
            $op_args['not'] = !Utils::array_get_key($op_args, 'not', false);
        }
        return new $op_args['class'](static::class, $args[0], $op_args);
    }
}
