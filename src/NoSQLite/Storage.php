<?php

namespace NoSQLite;

use NoSQLite\Collection\SimpleCollection;

class Storage {

    protected $db;
    protected $transactions_counter = 0;

    public function __construct($file) {
        $this->db = new \SQLite3($file);
    }

    /**
     * @param string $name name of collection
     * @param null|string $class class for collection instance, default SimpleCollection
     * @return \NoSQLite\Collection\IndexedCollection | SimpleCollection
     */
    public function getCollection($name, $class=null) {
        if (is_null($class)) {
            $class = SimpleCollection::class;
        }
        return new $class($this, $name);
    }

    protected function _execute($query) {
        return $this->db->query($query);
    }

    protected function _execute_with_args($query, $args) {
        $query = $this->db->prepare($query);
        foreach ($args as $placeholder => $value) {
            if (is_null($value)) {
                $type = SQLITE3_NULL;
            } elseif (is_bool($value) || is_int($value)) {
                $type = SQLITE3_INTEGER;
                $value = (int)$value;
            } elseif (is_float($value)) {
                $type = SQLITE3_FLOAT;
            } else {
                $type = SQLITE3_TEXT;
            }
            $query->bindValue($placeholder, $value, $type);
        }
        return $query->execute();
    }

    public function execute($query, array $args=null) {
        if ($args) {
            $result = $this->_execute_with_args($query, $args);
        } else {
            $result = $this->_execute($query);
        }
        if ($this->db->lastErrorCode()) {
            $sqlite_last_error = $this->db->lastErrorMsg();
            throw new \Exception("Query failed: {$sqlite_last_error}");
        }
        return $result;
    }

    public function fetchRow($query, array $args=null, $mode=SQLITE3_ASSOC) {
        $r = $this->execute($query, $args);
        $row = $r->fetchArray($mode);
        return $row;
    }

    public function fetchValue($query, array $args=null) {
        $r = $this->fetchRow($query, $args, SQLITE3_NUM);
        return $r[0];
    }

    public function getDB() {
        return $this->db;
    }

    public function begin() {
        if (!$this->transactions_counter) {
            $this->execute("BEGIN");
        }
        $this->transactions_counter++;
    }

    public function end() {
        if ($this->transactions_counter == 1) {
            $this->execute("END");
        }
        $this->transactions_counter--;
    }

    public function getTableFields($table) {
        $fields = [];
        $results = $this->db->query("PRAGMA table_info({$table})");
        while ($row = $results->fetchArray()) {
            $fields []= $row['name'];
        }
        return $fields;
    }
}
