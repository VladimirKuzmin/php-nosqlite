<?php

namespace NoSQLite\Field\Base;

use NoSQLite\Object;

abstract class AbstractField extends Object {
    protected $name;

    protected function buildName() {
        $names = func_get_args();
        return 'k'.md5(get_called_class().'#'.implode("\n", $names));
    }

    public function getName() {
        return $this->name;
    }

    public function __toString() {
        return $this->getName();
    }

    /**
     * @return string
     */
    abstract function getFieldType();

    /**
     * @param $obj array|\NoSQLite\Document\Document
     * @return string|int|float|\DateTime
     */
    abstract function apply($obj);
} 
