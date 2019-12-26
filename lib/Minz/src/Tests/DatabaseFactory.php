<?php

namespace Minz\Tests;

/**
 * Allow to create objects in database with default values during tests.
 *
 * It is often needed to create objects to test a behaviour that depends on
 * existing data. It makes the tests harder to write and read because database
 * constraints imply to set data that are not important to the current tested
 * code.
 *
 * The DatabaseFactory class allows to setup default values when creating such
 * objects.
 *
 * It needs to be initialized first with the DatabaseFactory::addFactory method
 * to register a factory with its DAO class and default values. For example:
 *
 *     \Minz\Tests\DatabaseFactory::addFactory(
 *         'rabbits',
 *         '\MyApp\models\dao\Rabbit',
 *         [
 *             'name' => 'Bugs',
 *             'age' => 1,
 *         ]
 *     );
 *
 * Then, you can use it by initializing a factory:
 *
 *     $rabbits_factory = new \Minz\Tests\DatabaseFactory('rabbits');
 *     $id = $rabbits_factory->create();
 *
 *     $dao = new \MyApp\models\dao\Rabbit();
 *     $rabbit_values = $dao->find($id);
 *     $this->assertSame('Bugs', $rabbits_values['name']);
 *
 * The create method takes values that override the default ones. It allows to
 * set data that is really important while letting uninteresting DB constraints
 * to the factory. It just merges default values with the given ones and
 * delegate the creation to the DAO class.
 *
 * A default value can contain a callable function to be executed at the
 * runtime. It is useful to create references to other factories:
 *
 *     \Minz\Tests\DatabaseFactory::addFactory(
 *         'friends',
 *         '\MyApp\models\dao\Rabbit',
 *         [
 *             'name' => 'Martin',
 *             'rabbit_id' => function () {
 *                 $rabbits_factory = new \Minz\Tests\DatabaseFactory('rabbits');
 *                 return $rabbits_factory->create();
 *             },
 *         ]
 *     );
 *
 * It is recommended:
 *
 * - to give default values only for required data (i.e. NOT NULL constraints)
 * - to never relies on default values or, in other words, to always make
 *   explicit the values you are testing
 *
 * Registered factories are automatically available in integration tests via
 * `self::$factories[$factory_name]`.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class DatabaseFactory
{
    /** @var array */
    protected static $factories = [];

    /** @var string */
    protected $factory_name;

    /** @var string */
    protected $dao_name;

    /** @var array */
    protected $default_values;

    /**
     * @param string $factory_name
     */
    public function __construct($factory_name)
    {
        $factory = self::$factories[$factory_name];
        $this->factory_name = $factory_name;
        $this->dao_name = $factory['dao_name'];
        $this->default_values = $factory['default_values'];
    }

    /**
     * @param array $values
     *
     * @return integer|string|boolean Return the result of DatabaseModel::create
     *
     * @see \Minz\DatabaseModel
     */
    public function create($values = [])
    {
        $default_values = [];
        foreach ($this->default_values as $property => $value) {
            if (is_callable($value) && !isset($values[$property])) {
                $value = $value();
            }
            $default_values[$property] = $value;
        }

        $dao = new $this->dao_name();
        $values = array_merge($default_values, $values);
        return $dao->create($values);
    }

    /**
     * @param string $factory_name
     * @param string $dao_name
     * @param array $default_values
     */
    public static function addFactory($factory_name, $dao_name, $default_values)
    {
        self::$factories[$factory_name] = [
            'dao_name' => $dao_name,
            'default_values' => $default_values,
        ];
    }

    /**
     * @return array
     */
    public static function factories()
    {
        return self::$factories;
    }
}
