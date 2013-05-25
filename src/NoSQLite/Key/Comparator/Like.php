<?php
namespace NoSQLite\Key\Comparator;


use NoSQLite\Utils;

class Like extends BaseComparator {

    protected $template = null;
    protected $operator = 'LIKE';

    protected function setArgs($args) {
        $this->template = $args['template'];
        $this->not = Utils::array_get_key($args, 'not', false);
    }

    protected function _getWhereTemplate() {
        // 1 - field, 2 - operator, 3 - placeholder for value
        return '%1$s %2$s %3$s ESCAPE \'\\\'';
    }

    public function getValue() {
        $val = parent::getValue();
        if ($this->template) {
            $val = str_replace('%', '\%', $val);
            $val = sprintf($this->template, $val);
        }
        return $val;
    }
} 
