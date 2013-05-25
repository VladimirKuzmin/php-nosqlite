<?php

namespace NoSQLite\Document;


class ArrayObject extends \ArrayObject {

    static protected function isArray($data) {
        return
            is_array($data)
            || (($data instanceof parent) && !($data instanceof self));
    }

    public function __construct($data) {
        $data = (array)$data;
        foreach ($data as $key => $value) {
            if (self::isArray($value)) {
                $data[$key] = new self($value);
            }
        }
        parent::__construct($data);
    }

    public function offsetSet($key, $value) {
        if (self::isArray($value)) {
            $value = new self($value);
        }
        parent::offsetSet($key, $value);
    }

    public function append($value) {
        if (self::isArray($value)) {
            $value = new self($value);
        }
        parent::append($value);
    }

    public function toArray() {
        return $this->_toArray((array)$this);
    }

    protected function _toArray($data) {
        foreach ($data as $k => $v) {
            if ($v instanceof self) {
                $data[$k] = $v->toArray();
            } elseif (is_object($v)) {
                if (is_callable(array($v, '__toString'))) {
                    $data[$k] = (string)$v;
                } else {
                    $data[$k] = $this->_toArray((array)$v);
                }
            } elseif (is_array($v)) {
                $data[$k] = $this->_toArray($v);
            }
        }
        return $data;
    }

    public function match(array $condition) {
        // TODO: do one-pass initialization and matching
        $match = true;
        foreach ($condition as $k => $sub) {
            if (!$this->offsetExists($k)) {
                return false;
            }
            if (is_array($sub)) {
                $obj = new self($this[$k]);
                /** @var $obj self */
                $match = $obj->match($sub);
            } else {
                $match = ($this[$k] == $sub);
            }
            if (!$match) {
                return $match;
            }
        }
        return $match;
    }

}
