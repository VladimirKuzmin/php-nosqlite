<?php

class TestDb {

    protected $filename;

    public function __construct() {
        $this->filename = __DIR__.'/tmp_db/test_db_'.md5(rand().time());
    }

    public function __toString() {
        return $this->filename;
    }

    public function __destruct() {
        unlink($this->filename);
    }

}


class TestCollection {

    protected $name;
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        $this->name = 'test_'.md5(rand().time());
    }

    public function __toString() {
        return $this->getName();
    }

    public function getDb() {
        return $this->db;
    }

    public function getName() {
        return $this->name;
    }
}
