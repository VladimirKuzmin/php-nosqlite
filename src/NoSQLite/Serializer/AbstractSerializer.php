<?php

namespace NoSQLite\Serializer;


abstract class AbstractSerializer {
    abstract public function serialize($data);
    abstract public function unserialize($data);
}