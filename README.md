php-nosqlite
============

NoSQL wrapper for SQLite.

The library allows to:
 - store documents in database
 - search documents by id or by matching pattern
 - use index on any field of document
 
TODO:
 - refactor documents and queries
 - create aggregation framework as awesome as MongoDB has
 - write documentation and tests
 - add join-like functionality
 
Usage
============

```php

use NoSQLite\Collection\IndexedCollection;
use NoSQLite\Document\Document;
use NoSQLite\Index\Index;
use NoSQLite\Key\String as StringKey;
use NoSQLite\Storage;

$storage = new Storage('file.nosqlite');

// create simple collection without indexes
$collection = $storage->getCollection('collection_name');

// create indexed collection
$collection = $storage->getCollection(
    'collection_name', IndexedCollection::getClass());

// save document
$id = $collection->save(new Document([
    'author' => [
        'name' => 'Douglas',
        'surname' => 'Adams',
    ],
    'books' => ["The Hitchhiker's Guide to the Galaxy", "Dirk Gently"],
]));

// get document by id
$collection->get($id);

// search by pattern matching
$collection->find([
    'author' => ['name' => 'Douglas']
]);

// use comparator in pattern
$collection->find([
    'author' => ['surname' => StringKey::not_contains('Rowling')]
]);

// ensure index on nested field author.name (type: string)
$collection->ensureIndex(new Index(new StringKey('author', 'name')));

// from now this query uses index
$collection->find([
    'author' => ['name' => StringKey::startswith('Doug')]
]);
```
