<?php

namespace NoSQLite\Query;


use NoSQLite\Collection\SimpleCollection;
use NoSQLite\Query\State\PossibleQuery;
use NoSQLite\Query\State\ImpossibleQuery;
use NoSQLite\Query\State\ImpossibleQueryException;

class Query {

    protected $collection = null;
    protected $where = array();

    /**
     * @var null|PossibleQuery|ImpossibleQuery
     */
    protected $query_state = null;

    public function __construct(SimpleCollection $collection) {
        $this->collection = $collection;
        $this->query_state = PossibleQuery::instance();
    }

    /**
     * @param array $where
     * @return Query
     */
    public function filter(array $where) {
        try {
            $this->where = $this->query_state->mergeWhere($this->where, $where);
        } catch (ImpossibleQueryException $e) {
            $this->where = null;
            $this->query_state = ImpossibleQuery::instance();
        }
        return $this;
    }

    public function execute() {
        return $this->query_state->execute($this->where, $this->collection);
    }

    public function find(array $where) {
        return $this->filter($where)->execute();
    }
} 