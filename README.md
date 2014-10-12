php-nosqlite
============

NoSQL wrapper for SQLite.

Features:
 - searching documents by id or by matching a pattern
 - able to use an index on any property of document
 
TODO:
 - nothing
 
Usage
============

```php

use NoSQLite\Collection\IndexedCollection;
use NoSQLite\Document\Document;
use NoSQLite\Index\Index;
use NoSQLite\Field\String;
use NoSQLite\Storage;

$storage = new Storage('file.nosqlite');

// create simple collection without indexes
$collection = $storage->getCollection('collection_name');

// create indexed collection
$collection = $storage->getCollection(
    'collection_name', IndexedCollection::class);

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
    'author' => ['name' => 'Douglas']]);

// use comparator in pattern
$collection->find([
    'author' => ['surname' => String::not_contains('Rowling')]]);

// ensure index on nested field author.name (type: string)
$collection->ensureIndex(new Index(new String('author', 'name')));

// from now this query uses index
$collection->find([
    'author' => ['name' => String::startswith('Doug')]]);
```
