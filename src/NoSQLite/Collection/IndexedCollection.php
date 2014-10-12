<?php

namespace NoSQLite\Collection;


use NoSQLite\Document\Document;
use NoSQLite\Index\Index;
use NoSQLite\Field\Base\AbstractField;
use NoSQLite\Field\Comparator\BaseComparator;
use NoSQLite\Serializer\AbstractSerializer;
use NoSQLite\Storage;

class IndexedCollection extends SimpleCollection {

    protected $keys_table;
    protected $key_instances_table;

    protected $keys = null;
    protected $indices = null;
    protected $key_instances = null;

    protected $keys_to_append = array();
    protected $indices_to_append = array();

    public function __construct(Storage $storage, $name, AbstractSerializer $serializer=null) {
        parent::__construct($storage, $name, $serializer);
        $this->indices_table = $name.'__indices';
        $this->keys_table = $name.'__keys';
        $this->key_instances_table = $name.'__inst';
    }

    public function getKeys() {
        if (is_null($this->keys)) {
            $this->loadKeys();
        }
        return $this->keys;
    }

    public function loadKeys($create_tables=true) {
        if ($create_tables) {
            $this->storage->execute(
                "CREATE TABLE IF NOT EXISTS {$this->keys_table} (id INTEGER)");
            $this->storage->execute(
                "CREATE UNIQUE INDEX IF NOT EXISTS id ON {$this->keys_table} (id)");
            $this->storage->execute(
                "CREATE TABLE IF NOT EXISTS {$this->key_instances_table} (
                    class VARCHAR(255), field TEXT)");
        }
        $this->keys = $this->storage->getTableFields($this->keys_table);
        $r = $this->storage->execute("SELECT class, field FROM {$this->key_instances_table}");
        $this->key_instances = array();
        while (false !== ($row = $r->fetchArray(SQLITE3_NUM))) {
            $this->key_instances []=
                new $row[0]($this->serializer->unserialize($row[1]));
        }
    }

    public function getIndices() {
        if (is_null($this->indices)) {
            $this->loadIndices();
        }
        return $this->indices;
    }

    public function loadIndices() {
        $this->indices = [];
        $results = $this->storage->execute("
            SELECT name FROM sqlite_master
            WHERE type='index' AND tbl_name=:tbl",
            ['tbl' => $this->keys_table]
        );
        while ($row = $results->fetchArray()) {
            $this->indices []= $row['name'];
        }
    }

    public function ensureIndex(Index $index) {
        $this->ensureIndices($index);
    }

    public function ensureIndices() {
        $indices = func_get_args();
        $this->db->query('BEGIN IMMEDIATE TRANSACTION');
        try {
            $existing_keys = $this->getKeys();
            $existing_indices = $this->getIndices();
            foreach ($indices as $index) {
                if (!($index instanceof Index)) {
                    throw new \Exception('Invalid index type');
                }
                if (in_array((string)$index, $existing_indices)) {
                    continue;
                }
                foreach ($index->getKeys() as $key) {
                    if (in_array((string)$key, $existing_keys)) {
                        continue;
                    }
                    $this->addNewKey($key);
                    $existing_keys []= (string)$key;
                }
                $this->addNewIndex($index);
                $existing_indices []= (string)$index;
            }
            $this->flushNewIndices();
            $this->db->query('COMMIT TRANSACTION');
        } catch (\Exception $e) {
            $this->db->query('ROLLBACK TRANSACTION');
            throw $e;
        }
    }

    protected function addNewKey($key) {
        $this->keys_to_append []= $key;
    }

    protected  function addNewIndex($index) {
        $this->indices_to_append []= $index;
    }

    protected function flushNewIndices() {
        if ($this->indices_to_append) {
            $this->flushNewKeys();
            /** @var $index Index */
            foreach ($this->indices_to_append as $index) {
                $this->db->query("CREATE INDEX IF NOT EXISTS {$index} ON {$this->keys_table} ("
                    .implode(',', $index->getKeys()).")");
            }
            $this->loadIndices();
        }
        $this->indices_to_append = [];
    }

    protected function flushNewKeys() {
        if ($this->keys_to_append) {
            $add = $select_fields = $fields = [];
            /** @var $key \NoSQLite\Field\Base\Field */
            foreach ($this->keys_to_append as $key) {
                $add []= [
                    'key' => $key,
                    'add' => "ADD COLUMN {$key} ".$key->getFieldType()];
                $function_name = $this->registerKeyHandler($key);
                $fields []= (string)$key;
                $select_fields []= "{$function_name}(id, data)";
            }
            $this->keys_to_append = array();
            foreach ($add as $add_column) {
                $sql = "ALTER TABLE {$this->keys_table} {$add_column['add']}";
                $this->storage->execute($sql);
                $sql = "INSERT INTO {$this->key_instances_table} (class, field) VALUES (:class, :field)";
                $this->storage->execute(
                    $sql, [
                        'class' => $key::getClass(),
                        'field' => $this->serializer->serialize($key->getField())]);
            }
            $sql = "
                REPLACE INTO {$this->keys_table}
                    (id,".implode(',', $fields).")
                    SELECT id, ".implode(',', $select_fields)." FROM {$this->data_table}";
            $this->storage->execute($sql);
            $this->loadKeys(false);
        }
    }

    public function getKeysTable() {
        return $this->keys_table;
    }

    protected function extractComparatorsFromCondition($condition, array $context=[]) {
        $cmp = [];
        foreach ($condition as $k => $v) {
            array_push($context, $k);
            if ($v instanceof BaseComparator) {
                $cmp []= [(array)$context /* make a copy of current context */, $v];
                unset($condition[$k]);
            } else if (is_array($v) || ($v instanceof \ArrayObject)) {
                list($cmp_new, $v_new) = $this->extractComparatorsFromCondition($v, $context);
                $cmp = array_merge($cmp, $cmp_new);
                if (empty($v_new)) {
                    unset($condition[$k]);
                } else {
                    $condition[$k] = $v_new;
                }
            }
            array_pop($context);
        }
        return [$cmp, $condition];
    }

    public function find(array $conditions) {
        list($comparators, $conditions) = $this->extractComparatorsFromCondition($conditions);
        $keys = array_flip($this->getKeys());
        $where_func = $where_keys = [];
        foreach ($comparators as list($context, $cmp)) {
            /** @var $cmp BaseComparator */
            $key_class = $cmp->getKeyClass();
            //TODO: unpack context and remove workaround from FieldKey::__construct()
            /** @var $key AbstractField */
            $key = new $key_class($context);
            $function = $this->registerKeyHandler($key);
            if (array_key_exists($key->getName(), $keys)) {
                $where_keys []= [$cmp, $key->getName()];
            } else {
                $where_func []= [$cmp, $function];
            }
        }
        $query = "SELECT * FROM {$this->data_table} D";
        if ($where_keys) {
            $query .= " INNER JOIN {$this->keys_table} K ON K.id=D.id";
        }
        $where = $query_args = [];
        foreach ($where_keys as $w) {
            $placeholder = ':'.md5(implode('::', $w));
            /** @var $cmp BaseComparator */
            $cmp = $w[0];
            $where []= $cmp->getWhere("K.{$w[1]}", $placeholder);
            $query_args[$placeholder] = $cmp->getValue();
        }
        foreach ($where_func as $w) {
            $placeholder = ':'.md5(implode('::', $w));
            /** @var $cmp BaseComparator */
            $cmp = $w[0];
            $where []= $cmp->getWhere("{$w[1]}(D.id, D.data)", $placeholder);
            $query_args[$placeholder] = $cmp->getValue();
        }
        if ($where) {
            $query .= " WHERE ".implode(' AND ', $where);
        }
        return parent::_find($conditions, $query, $query_args);
    }

    public function saveDocument(Document $doc) {
        $this->storage->begin();
        $res = parent::saveDocument($doc);
        if ($this->key_instances) {
            $fields = $values = [];
            foreach ($this->key_instances as $key) {
                /** @var $key AbstractField */
                $fields []= (string)$key;
                $values[":{$key}"] = $key->apply($doc);
            }
            $sql = "
                REPLACE INTO {$this->keys_table}
                    (id,".implode(',', $fields).")
                    VALUES
                    (:id,".implode(',', array_keys($values)).")";
            $values['id'] = $doc->getId();
            $this->storage->execute($sql, $values);
        }
        $this->storage->end();
        return $res;
    }
}
