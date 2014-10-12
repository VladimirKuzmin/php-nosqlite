<?php

namespace NoSQLite\Collection;


use NoSQLite\Document\Document;
use NoSQLite\Field\Base\AbstractField;
use NoSQLite\Object;
use NoSQLite\Query\Query;
use NoSQLite\Serializer\AbstractSerializer;
use NoSQLite\Serializer\PHP;
use NoSQLite\Storage;

class SimpleCollection extends Object {

    protected $db;
    protected $name;
    protected $data_table;
    protected $serializer;
    protected $registered_handlers = [];
    protected $storage;

    public function __construct(Storage $storage, $name, AbstractSerializer $serializer=null) {
        $this->db = $storage->getDB();
        $this->storage = $storage;
        $this->name = $name;
        $this->data_table = $name.'__data';
        if (is_null($serializer)) {
            $serializer = new PHP();
        }
        $this->serializer = $serializer;
        $this->storage->execute(
            "CREATE TABLE IF NOT EXISTS {$this->data_table} (id INTEGER, data TEXT, PRIMARY KEY(id))");
    }

    /**
     * @return \NoSQLite\Storage
     */
    public function getStorage() {
        return $this->storage;
    }

    protected function __sqlite_tableExists($table) {
        return $this->storage->fetchValue(
            "SELECT 1 FROM sqlite_master WHERE type='table' AND name=:tbl", ['tbl' => $table]);
    }

    public function save($doc) {
        if (!($doc instanceof Document)) {
            $doc = new Document($doc);
        }
        return $this->saveDocument($doc);
    }

    public function saveAll(array $docs) {
        $this->storage->begin();
        foreach ($docs as $doc) {
            $this->save($doc);
        }
        $this->storage->end();
    }

    public function saveDocument(Document $doc) {
        $data = $doc->toArray();
        $str = $this->serializer->serialize($data);
        $id = $doc->getId();
        if (is_null($id)) {
            $this->storage->execute(
                "INSERT INTO {$this->data_table} (data) VALUES (:str)", ['str' => $str]);
            $doc['id'] = $this->db->lastInsertRowID();
        } else {
            $this->storage->execute(
                "INSERT OR REPLACE INTO {$this->data_table} (id, data) VALUES (:id, :str)",
                array('str' => $str, 'id' => $id));
        }
        return $doc->getId();
    }

    protected function unserializeDocument($data, $id) {
        return Document::restore($this->serializer->unserialize($data), $id, $this);
    }

    public function get($id) {
        $id = (int)$id;
        $data = $this->storage->fetchValue(
            "SELECT data FROM {$this->data_table} WHERE id=:id", ['id' => $id]);
        return $this->unserializeDocument($data, $id);
    }

    public function getDb() {
        return $this->db;
    }

    public function getDataTable() {
        return $this->data_table;
    }

    public function query() {
        return new Query($this);
    }

    protected function getUnserializeFunction() {
        static $unserialize = null;
        if (is_null($unserialize)) {
            $serializer = $this->serializer;
            $unserialize = function ($id, $data) use ($serializer) {
                static $_id, $_data;
                if ($id !== $_id) {
                    $_id = $id;
                    $_data = $serializer->unserialize($data);
                }
                return $_data;
            };
        }
        return $unserialize;
    }

    protected function registerKeyHandler(AbstractField $key) {
        $function_name = "f{$key}";
        if (!@$this->registered_handlers[$function_name]) {
            $unserialize = $this->getUnserializeFunction();
            $function = function ($id, $data) use ($unserialize, $key) {
                $data = $unserialize($id, $data);
                return $key->apply($data);
            };
            $res = $this->db->createFunction($function_name, $function, 2);
            if (!$res) {
                throw new \Exception("Can't register function {$function_name}");
            }
            $this->registered_handlers[$function_name] = true;
        }
        return $function_name;
    }

    protected function _find(array $conditions, $query, $query_args=null) {
        $r = $this->storage->execute($query, $query_args);
        $result = [];
        while (false !== ($row = $r->fetchArray(SQLITE3_NUM))) {
            $doc = $this->unserializeDocument($row[1], $row[0]);
            if ($doc->match($conditions)) {
                $result []= $doc;
            }
        }
        return $result;

    }

    public function find(array $conditions) {
        return $this->_find($conditions, "SELECT D.* FROM {$this->data_table}");
    }
}
