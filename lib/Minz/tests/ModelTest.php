<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testConstuctor()
    {
        $model = new Model(['property' => 'string']);

        $this->assertNull($model->property);
    }

    /**
     * @dataProvider validTypesProvider
     */
    public function testConstructorWithValidPropertyTypes($type)
    {
        $model = new Model(['property' => $type]);

        $property_declarations = $model->propertyDeclarations();
        $this->assertSame($type, $property_declarations['property']['type']);
    }

    public function testConstructorFailsIfPropertyTypeIsNotSupported()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_TYPE_INVALID);
        $this->expectExceptionMessage('`not a type` is not a valid property type.');

        new Model(['id' => 'not a type']);
    }

    public function testConstructorFailsIfValidatorIsUncallable()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_VALIDATOR_INVALID);
        $this->expectExceptionMessage('`not_callable` validator cannot be called.');

        new Model([
            'id' => [
                'type' => 'integer',
                'validator' => 'not_callable',
            ],
        ]);
    }

    public function testPropertyDeclarations()
    {
        $model = new Model(['id' => 'integer']);

        $property_declarations = $model->propertyDeclarations();

        $this->assertSame([
            'id' => [
                'type' => 'integer',
                'required' => false,
                'validator' => null,
            ],
        ], $property_declarations);
    }

    public function testToValues()
    {
        $model = new Model(['id' => 'integer']);
        $model->id = 42;

        $values = $model->toValues();

        $this->assertSame(42, $values['id']);
    }

    public function testToValuesWithUnsetValue()
    {
        $model = new Model(['id' => 'integer']);

        $values = $model->toValues();

        $this->assertNull($values['id']);
    }

    public function testToValuesWithUndeclaredProperty()
    {
        $model = new Model();
        $model->id = 42;

        $values = $model->toValues();

        $this->assertSame([], $values);
    }

    public function testToValuesWithDatetimeProperty()
    {
        $model = new Model(['created_at' => 'datetime']);
        $created_at = new \DateTime();
        $created_at->setTimestamp(1000);
        $model->created_at = $created_at;

        $values = $model->toValues();

        $this->assertSame(1000, $values['created_at']);
    }

    public function testToValuesWithUnsetDatetimeProperty()
    {
        $model = new Model(['created_at' => 'datetime']);

        $values = $model->toValues();

        $this->assertNull($values['created_at']);
    }

    public function testSetPropertyWithStringType()
    {
        $model = new Model(['foo' => 'string']);

        $model->setProperty('foo', 'bar');

        $this->assertSame('bar', $model->foo);
    }

    public function testSetPropertyWithIntegerType()
    {
        $model = new Model(['id' => 'integer']);

        $model->setProperty('id', 42);

        $this->assertSame(42, $model->id);
    }

    public function testSetPropertyWithIntegerTypeAndNull()
    {
        $model = new Model(['id' => 'integer']);

        $model->setProperty('id', null);

        $this->assertNull($model->id);
    }

    public function testSetPropertyWithBooleanType()
    {
        $model = new Model(['is_cool' => 'boolean']);

        $model->setProperty('is_cool', true);

        $this->assertTrue($model->is_cool);
    }

    public function testSetPropertyWithBooleanTypeAndNull()
    {
        $model = new Model(['is_cool' => 'boolean']);

        $model->setProperty('is_cool', null);

        $this->assertNull($model->is_cool);
    }

    public function testSetPropertyWithDatetimeType()
    {
        $model = new Model(['created_at' => 'datetime']);
        $date = new \Datetime();
        $date->setTimestamp(1000);

        $model->setProperty('created_at', $date);

        $this->assertSame(1000, $model->created_at->getTimestamp());
    }

    public function testSetPropertyWithDatetimeTypeAndNull()
    {
        $model = new Model(['created_at' => 'datetime']);

        $model->setProperty('created_at', null);

        $this->assertNull($model->created_at);
    }

    public function testSetPropertyWithValidator()
    {
        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);

        $model->setProperty('status', 'new');

        $this->assertSame('new', $model->status);
    }

    public function testSetPropertyWithValidatorIsNotCalledIfNull()
    {
        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return 'An error? Na, the validator is not called!';
                },
            ],
        ]);

        $model->setProperty('status', null);

        $this->assertNull($model->status);
    }

    public function testSetPropertyFailsIfRequiredPropertyIsNull()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_REQUIRED);
        $this->expectExceptionMessage('Required `id` property is missing.');

        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        $model->setProperty('id', null);
    }

    public function testSetPropertyFailsIfValidatorReturnsFalse()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_INVALID);
        $this->expectExceptionMessage('`status` property is invalid (not valid).');

        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);

        $model->setProperty('status', 'not valid');
    }

    public function testSetPropertyFailsIfValidatorReturnsCustomMessage()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_INVALID);
        $this->expectExceptionMessage(
            '`status` property is invalid (new): a custom message error.'
        );

        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return 'a custom message error';
                },
            ],
        ]);

        $model->setProperty('status', 'new');
    }

    public function testSetPropertyFailsIfUndeclaredProperty()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_UNDECLARED);
        $this->expectExceptionMessage(
            '`status` property has not been declared.'
        );

        $model = new Model([]);

        $model->setProperty('status', 'new');
    }

    public function testFromValuesWithString()
    {
        $model = new Model(['foo' => 'string']);

        $model->fromValues(['foo' => 'bar']);

        $this->assertSame('bar', $model->foo);
    }

    public function testFromValuesWithInteger()
    {
        $model = new Model(['id' => 'integer']);

        $model->fromValues(['id' => '42']);

        $this->assertSame(42, $model->id);
    }

    public function testFromValuesWithIntegerZero()
    {
        $model = new Model(['id' => 'integer']);

        $model->fromValues(['id' => '0']);

        $this->assertSame(0, $model->id);
    }

    public function testFromValuesWithBoolean()
    {
        $model = new Model(['is_cool' => 'boolean']);

        // @todo check value returned by SQLite
        $model->fromValues(['is_cool' => 'true']);

        $this->assertTrue($model->is_cool);
    }

    public function testFromValuesWithBooleanFalse()
    {
        $model = new Model(['is_cool' => 'boolean']);

        // @todo check value returned by SQLite
        $model->fromValues(['is_cool' => 'false']);

        $this->assertFalse($model->is_cool);
    }

    public function testFromValuesWithDatetime()
    {
        $model = new Model(['created_at' => 'datetime']);

        $model->fromValues(['created_at' => '1000']);

        $this->assertInstanceOf(\DateTime::class, $model->created_at);
        $this->assertSame(1000, $model->created_at->getTimestamp());
    }

    public function testFromValuesWithDatetimeTimestampZero()
    {
        $model = new Model(['created_at' => 'datetime']);

        $model->fromValues(['created_at' => '0']);

        $this->assertSame(0, $model->created_at->getTimestamp());
    }

    public function testFromValuesWithValidator()
    {
        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);

        $model->fromValues(['status' => 'new']);

        $this->assertSame('new', $model->status);
    }

    public function testFromValuesFailsIfMissingRequiredProperty()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_REQUIRED);
        $this->expectExceptionMessage('Required `id` property is missing.');

        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        $model->fromValues([]);
    }

    public function testFromValuesFailsIfRequiredPropertyIsNull()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_REQUIRED);
        $this->expectExceptionMessage('Required `id` property is missing.');

        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        $model->fromValues(['id' => null]);
    }

    public function testFromValuesFailsIfIntegerTypeDoesNotMatch()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_TYPE_INVALID);
        $this->expectExceptionMessage('`id` property must be an integer.');

        $model = new Model(['id' => 'integer']);

        $model->fromValues(['id' => 'not an integer']);
    }

    public function testFromValuesFailsIfDatetimeTypeDoesNotMatch()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_TYPE_INVALID);
        $this->expectExceptionMessage('`created_at` property must be a timestamp.');

        $model = new Model(['created_at' => 'datetime']);

        $model->fromValues(['created_at' => 'not a timestamp']);
    }

    public function testFromValuesFailsIfBooleanTypeDoesNotMatch()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_TYPE_INVALID);
        $this->expectExceptionMessage('`is_cool` property must be a boolean.');

        $model = new Model(['is_cool' => 'boolean']);

        $model->fromValues(['is_cool' => 'not a boolean']);
    }

    public function testFromValuesFailsIfValidatorReturnsFalse()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_INVALID);
        $this->expectExceptionMessage('`status` property is invalid (not valid).');

        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);

        $model->fromValues(['status' => 'not valid']);
    }

    public function testFromValuesFailsIfUndeclaredProperty()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_UNDECLARED);
        $this->expectExceptionMessage(
            '`status` property has not been declared.'
        );

        $model = new Model([]);

        $model->fromValues(['status' => 'new']);
    }

    public function validTypesProvider()
    {
        return [
            ['string'],
            ['integer'],
            ['datetime'],
            ['boolean'],
        ];
    }
}
