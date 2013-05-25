<?php

namespace NoSQLite\Query\State;


use NoSQLite\Collection\SimpleCollection;

interface IQueryState {
    function mergeWhere($w1, $w2);
    function execute($where, SimpleCollection $collection);
} 