<?php

use NoSQLite\Collection\IndexedCollection;
use NoSQLite\Document\Document;
use NoSQLite\Index\Index;
use NoSQLite\Field\String;
use NoSQLite\Storage;

require_once 'include.php';

class IndexedCollectionTest extends PHPUnit_Framework_TestCase {

    /**
     * @var IndexedCollection
     */
    protected $collection;

    function setUp() {
        $test_collection = new TestCollection(new TestDb());
        $storage = new Storage((string)$test_collection->getDb());
        $this->collection = $storage->getCollection(
            $test_collection, IndexedCollection::class);

    }

    function testSetAndGet() {
        $data = array(
            'a' => 1,
            'b' => [1,2,3],
            'c' => null,
        );
        $id = $this->collection->save(new Document($data));
        $this->assertGreaterThan(0, $id, "ID must be positive number");
        $restored_data = $this->collection->get($id);
        $this->assertEquals($restored_data->getId(), $id, "Restored data contains invalid ID");
        $this->assertEquals($restored_data->toArray(), $data, "Data and restored data are different");
    }

    function testEnsureIndices() {
        $index_test = new Index(new String('test'));
        $index_xxx = new Index(new String('xxx'));
        $index_nested = new Index(new String('nested', 'key'));
        $this->collection->save(new Document(['test' => $index_test, 'xxx' => $index_xxx, 'nested' => ['key' => $index_nested]]));
        $this->collection->save(new Document(['test' => [1], 'xxx' => [2]]));
        $this->collection->save(new Document(['id' => 4, 'test' => 'test', 'xxx' => (object)[]]));
        $this->collection->ensureIndices($index_test, $index_xxx, $index_nested);

        $keys_table = $this->collection->getKeysTable();
        $results = $this->collection->getStorage()->execute("SELECT * FROM {$keys_table}");
        $keys_data = [];
        $index_test = $index_test->getKeys()[0];
        $index_xxx = $index_xxx->getKeys()[0];
        $index_nested = $index_nested->getKeys()[0];
        while (false !== ($row = $results->fetchArray())) {
            $keys_data[$row['id']] = [
                "{$index_test}" => $row["{$index_test}"],
                "{$index_xxx}" => $row["{$index_xxx}"],
                "{$index_nested}" => $row["{$index_nested}"],
            ];
        }
        $keys_data_expected = [
            '1' => [
                "{$index_test}" => "[{$index_test}]",
                "{$index_xxx}" => "[{$index_xxx}]",
                "{$index_nested}" => "[{$index_nested}]",
            ],
            '2' => [
                "{$index_test}" => null,
                "{$index_xxx}" => null,
                "{$index_nested}" => null,
            ],
            '4' => [
                "{$index_test}" => 'test',
                "{$index_xxx}" => null,
                "{$index_nested}" => null,
            ]
        ];
        $this->assertEquals($keys_data_expected, $keys_data, 'unexpected keys data');
    }

    function testSelect() {
        $this->collection->save([
            'test' => 'test',
            'xxx' => 'xxx'
        ]);
        $this->collection->save([
            'test' => [1],
            'xxx' => [2]
        ]);
        $this->collection->save([
            'id' => 4,
            'test' => 'test',
            'xxx' => (object)[]
        ]);

        $result = $this->collection->find(['test' => 'test']);

        function getIds($result) {
            $ids = [];
            foreach ($result as $doc) {
                $ids []= $doc['id'];
            }
            ksort($ids);
            return $ids;
        }

        $ids = getIds($result);
        $this->assertEquals($ids, [1, 4], 'bad result');

        $result = $this->collection->find(['test' => 'no test']);
        $this->assertEquals($result, [], 'bad result');

        $this->collection->save([
            'id' => 5,
            'test' => [
                'nested' => 'test123'
            ]
        ]);

        $result = $this->collection->find([
            'test' => [
                'nested' => String::startswith('test')
            ]
        ]);

        $ids = getIds($result);
        $this->assertEquals($ids, [5], 'bad result (nested search)');
        $result = $this->collection->find([
            'test' => [
                'nested' => String::not_startswith('test')
            ]
        ]);

        $ids = getIds($result);
        $this->assertEquals($ids, [1, 2, 4], 'bad result (nested search)');
    }

} 
