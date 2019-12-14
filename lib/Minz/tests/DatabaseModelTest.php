<?php

namespace Minz;

use PHPUnit\Framework\TestCase;
use AppTest\models;

class DatabaseModelTest extends TestCase
{
    public function setUp(): void
    {
        $sql_schema = <<<'SQL'
CREATE TABLE friends (
    id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    name varchar(255) NOT NULL,
    address varchar(255)
);

CREATE TABLE rabbits (
    rabbit_id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    name varchar(255) NOT NULL,
    friend_id integer NOT NULL,
    FOREIGN KEY (friend_id) REFERENCES friends(id)
);
SQL;
        $database = Database::get();
        $database->exec($sql_schema);
    }

    public function tearDown(): void
    {
        Database::drop();
    }

    public function testConstructorFailsIfTableNameIsInvalid()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            "invalid'name is not a valid table name in the Minz\DatabaseModel model."
        );

        new DatabaseModel("invalid'name", 'id', ['id', 'name']);
    }

    public function testConstructorFailsIfColumnIsInvalid()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            "invalid'name is not a valid column name in the Minz\DatabaseModel model."
        );

        new DatabaseModel('rabbits', 'id', ['id', "invalid'name"]);
    }

    public function testConstructorFailsIfPrimaryKeyIsntIncludedInProperties()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'Primary key id must be in properties in the Minz\DatabaseModel model.'
        );

        new DatabaseModel('rabbits', 'id', ['name']);
    }

    public function testCount()
    {
        $dao = new models\dao\Friend();

        $number_of_friends = $dao->count();

        $this->assertSame(0, $number_of_friends);
    }

    public function testCreate()
    {
        $dao = new models\dao\Friend();
        $this->assertSame(0, $dao->count());

        $id = $dao->create([
            'name' => 'Joël',
        ]);

        $this->assertSame(1, $dao->count());
        $this->assertSame(1, $id);
    }

    public function testCreateWithDependency()
    {
        $friend_dao = new models\dao\Friend();
        $rabbit_dao = new models\dao\Rabbit();

        $friend_id = $friend_dao->create([
            'name' => 'Joël',
        ]);
        $rabbit_id = $rabbit_dao->create([
            'name' => 'Bugs',
            'friend_id' => $friend_id,
        ]);

        $this->assertSame(1, $friend_dao->count());
        $this->assertSame(1, $friend_id);
        $this->assertSame(1, $rabbit_dao->count());
        $this->assertSame(1, $rabbit_id);
    }

    public function testCreateFailsIfNoValuesIsPassed()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            "AppTest\models\dao\Friend::create method expect values to be passed."
        );

        $dao = new models\dao\Friend();

        $dao->create([]);
    }

    public function testCreateFailsIfRequiredPropertyIsntSet()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'Error in SQL statement: NOT NULL constraint failed: friends.name (23000).'
        );

        $dao = new models\dao\Friend();

        $dao->create([
            'address' => 'Home',
        ]);
    }

    public function testCreateFailsIfUnsupportedPropertyIsPassed()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'not_property is not declared in the AppTest\models\dao\Friend model.'
        );

        $dao = new models\dao\Friend();

        $dao->create([
            'name' => 'Joël',
            'not_property' => 'foo',
        ]);
    }

    public function testCreateFailsIfDependencyNotMet()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'Error in SQL statement: FOREIGN KEY constraint failed (23000).'
        );

        $dao = new models\dao\Rabbit();

        var_dump($dao->create([
            'name' => 'Bugs',
            'friend_id' => 42,
        ]));
    }

    public function testListAll()
    {
        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);
        $dao->create(['name' => 'Dorothé']);
        $this->assertSame(2, $dao->count());

        $friends = $dao->listAll();

        $this->assertSame(2, count($friends));
        $this->assertSame('Joël', $friends[0]['name']);
        $this->assertSame('Dorothé', $friends[1]['name']);
    }

    public function testListAllWithSelectingProperties()
    {
        $dao = new models\dao\Friend();
        $dao->create([
            'name' => 'Joël',
            'address' => 'Home',
        ]);
        $dao->create([
            'name' => 'Dorothé',
            'address' => 'Also home',
        ]);

        $friends = $dao->listAll(['address']);

        $this->assertSame(2, count($friends));
        $this->assertArrayNotHasKey('name', $friends[0]);
        $this->assertArrayNotHasKey('name', $friends[1]);
        $this->assertSame('Home', $friends[0]['address']);
        $this->assertSame('Also home', $friends[1]['address']);
    }

    public function testListAllWithSelectingPropertiesFailsIfUnsupportedProperty()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'not_property is not declared in the AppTest\models\dao\Friend model.'
        );

        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $dao->listAll(['not_property']);
    }

    public function testFind()
    {
        $dao = new models\dao\Friend();
        $id = $dao->create(['name' => 'Joël']);

        $joel = $dao->find($id);

        $this->assertSame('Joël', $joel['name']);
    }

    public function testFindWithNoMatchingData()
    {
        $dao = new models\dao\Friend();

        $is_someone = $dao->find(42);

        $this->assertNull($is_someone);
    }

    public function testFindBy()
    {
        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $joel = $dao->findBy(['name' => 'Joël']);

        $this->assertSame('Joël', $joel['name']);
    }

    public function testFindByWithNoMatchingData()
    {
        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $someone = $dao->findBy(['name' => 'Josy']);

        $this->assertNull($someone);
    }

    public function testFindByFailsWithEmptyValues()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage('It is expected values not to be empty.');

        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $dao->findBy([]);
    }

    public function testFindByFailsIfUnsupportedProperty()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'not_property is not declared in the AppTest\models\dao\Friend model.'
        );

        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $dao->findBy(['not_property' => 'foo']);
    }

    public function testListBy()
    {
        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);
        $dao->create(['name' => 'Joël']);

        $joels = $dao->listBy(['name' => 'Joël']);

        $this->assertSame(2, count($joels));
        $this->assertSame('Joël', $joels[0]['name']);
        $this->assertSame('Joël', $joels[1]['name']);
    }

    public function testListByWithNoMatchingData()
    {
        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $friends = $dao->listBy(['name' => 'Josy']);

        $this->assertSame([], $friends);
    }

    public function testListByFailsWithEmptyValues()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage('It is expected values not to be empty.');

        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $dao->listBy([]);
    }

    public function testListByFailsIfUnsupportedProperty()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'not_property is not declared in the AppTest\models\dao\Friend model.'
        );

        $dao = new models\dao\Friend();
        $dao->create(['name' => 'Joël']);

        $dao->listBy(['not_property' => 'foo']);
    }

    public function testUpdate()
    {
        $dao = new models\dao\Friend();
        $id = $dao->create(['name' => 'Joël']);

        $dao->update($id, [
            'name' => 'Joëlle'
        ]);

        $friend = $dao->find($id);
        $this->assertSame('Joëlle', $friend['name']);
    }

    public function testUpdateWithUnknownId()
    {
        $dao = new models\dao\Friend();

        $dao->update(42, [
            'name' => 'Joëlle'
        ]);

        $this->assertSame(0, $dao->count());
    }

    public function testUpdateFailsIfNoValuesIsPassed()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            "AppTest\models\dao\Friend::update method expect values to be passed."
        );

        $dao = new models\dao\Friend();
        $id = $dao->create(['name' => 'Joël']);

        $dao->update($id, []);
    }

    public function testUpdateFailsIfUnsupportedPropertyIsPassed()
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'not_property is not declared in the AppTest\models\dao\Friend model.'
        );

        $dao = new models\dao\Friend();
        $id = $dao->create(['name' => 'Joël']);

        $dao->update($id, [
            'not_property' => 'foo',
        ]);
    }

    public function testDelete()
    {
        $dao = new models\dao\Friend();
        $id = $dao->create(['name' => 'Joël']);
        $this->assertSame(1, $dao->count());

        $dao->delete($id);

        $this->assertSame(0, $dao->count());
    }

    public function testDeleteWithUnknownId()
    {
        $dao = new models\dao\Friend();
        $this->assertSame(0, $dao->count());

        $dao->delete(42);

        $this->assertSame(0, $dao->count());
    }
}
