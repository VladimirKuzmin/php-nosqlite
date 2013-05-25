<?php

namespace NoSQLite\Serializer;


class JSON extends AbstractSerializer {

    public function serialize($data) {
        return \json_encode($data);
    }

    public function unserialize($data) {
        return \json_decode($data, true);
    }

}