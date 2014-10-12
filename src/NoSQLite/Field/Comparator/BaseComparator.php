<?php

namespace NoSQLite\Field\Comparator;


use NoSQLite\Object;
use NoSQLite\Utils;

class BaseComparator extends Object {

    protected $operator = null;
    protected $not = false;

    /**
     * @var null|\NoSQLite\Field\Base\Field
     */
    protected $key_class = null;
    protected $value = null;

    public function __construct($key_class, $value, array $args) {
        $this->key_class = $key_class;
        $this->value = $value;
        $this->setArgs($args);
    }

    protected function setArgs($args) {
        $this->operator = $args['operator'];
        $this->not = Utils::array_get_key($args, 'not', false);
    }

    public function getKeyClass() {
        return $this->key_class;
    }

    public function getOperator() {
        return $this->operator;
    }

    protected function _getWhereTemplate() {
        // 1 - field, 2 - operator, 3 -placeholder for value
        return '%1$s %2$s %3$s';
    }

    public function getWhere($field, $placeholder) {
        $str = sprintf(
            $this->_getWhereTemplate(),
            $field, $this->getOperator(), $placeholder);
        if ($this->not) {
            $str = "({$field} IS NULL OR NOT ({$str}))";
        }
        return $str;
    }

    public function getValue() {
        $cls = $this->key_class;
        return $cls::serialize($this->value);
    }

    public function __toString() {
        $where = $this->getWhere('field', 'placeholder');
        $value = $this->getValue();
        return "<'{$where}'> for <'{$value}'>";
    }
} 
