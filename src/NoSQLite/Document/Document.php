<?php

namespace NoSQLite\Document;

use NoSQLite\Collection\SimpleCollection;

/**
 * Class Document
 *
 * Document instances stored in a collections.
 * TODO: refactor and optimize it
 *
 * @package NoSQLite\Document
 */
class Document extends ArrayObject {

    /**
     * @var null|SimpleCollection
     */
    protected $collection = null;

    static public function restore($data, $id=null, SimpleCollection $collection=null) {
        /** @var $obj self */
        $obj = new static($data);
        if ($collection) {
            $obj->setCollection($collection);
        }
        if ($id) {
            $obj->setId($id);
        }
        return $obj;
    }

    public function toArray($deep=true, $saveId=false) {
        $data = (array)$this;
        if (!$saveId) {
            unset($data['id']);
        }
        if ($deep) {
            return $this->_toArray($data);
        } else {
            return $data;
        }
    }

    public function getId() {
        return $this->offsetExists('id')?$this['id']:null;
    }

    public function setId($id) {
        if (!is_int($id)) {
            throw new \Exception("Id must be an integer");
        }
        $this['id'] = (int)$id;
    }

    public function setCollection(SimpleCollection $collection) {
        $this->collection = $collection;
    }

    public function save() {
        if (!$this->collection) {
            throw new \Exception("Document doesn't belong to any collection");
        }
        $this->collection->save($this);
    }

    public function __destruct() {
        $this->collection = null;
    }

    static public function buildIfMatches(array $data, array $condition) {
        // TODO: do one-pass initialization and matching
        $obj = new self($data);
        /** @var $obj self */
        if ($obj->match($condition)) {
            return $obj;
        }
        return null;
    }
}
