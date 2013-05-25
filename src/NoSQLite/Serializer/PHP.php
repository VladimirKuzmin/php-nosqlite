<?php

namespace NoSQLite\Serializer;


class PHP extends AbstractSerializer {

    public function serialize($data) {
        return serialize($data);
    }

    public function unserialize($data) {
        return unserialize($data);
    }
}