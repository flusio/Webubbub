<?php

namespace Minz;

/**
 * A Model allows to declare a model with its properties.
 *
 * A property is data destined to be stored in the database. It has at least a
 * type and can be required and validated.
 *
 * The model can be exported to that database with the `toValues` method, and
 * imported with `fromValues`. Model class should be inherited and good
 * practices imply to create a constructor that declares the properties and
 * loads values. For example:
 *
 *     public function __construct($values)
 *     {
 *         parent::__construct(self::PROPERTIES);
 *         $this->fromValues($values);
 *     }
 *
 * If you want a constuctor accepting specific properties, you can declare a
 * static method:
 *
 *     public static function new($name)
 *     {
 *         return new MyModel([
 *             'name' => strip($name),
 *             'status' => 'new,
 *         ]);
 *     }
 *
 * This allows to load easily a model from database which is more common than
 * initializing a new model.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Model
{
    public const VALID_PROPERTY_TYPES = ['string', 'integer', 'datetime', 'boolean'];

    public const DEFAULT_PROPERTY_DECLARATION = [
        'type' => null,
        'required' => false,
        'validator' => null,
    ];

    /** @var array */
    protected $property_declarations;

    /**
     * Declare properties of the model.
     *
     * A declaration is an array where keys are property names, and the values
     * their declarations. A declaration can be a simple string defining a type
     * (string, integer, datetime or boolean), or an array with a required
     * `type` key and optional `required` and `validator` keys. For example:
     *
     *     [
     *         'id' => 'integer',
     *
     *         'name' => [
     *             'type' => 'string',
     *             'required' => true,
     *         ],
     *
     *         'status' => [
     *             'type' => 'string',
     *             'required' => true,
     *             'validator' => function ($status) {
     *                 return in_array($status, ['new', 'finished']);
     *             },
     *         ],
     *     ]
     *
     * The resulting model is initialized with its properties declared and set
     * to `null`. It must be loaded then with the `fromValues` method.
     *
     * A validator must return true if the value is correct, or false
     * otherwise. It also can return a string to detail the reason of the
     * error.
     *
     * @param array $property_declarations
     *
     * @throws \Minz\Errors\ModelPropertyError if type is invalid
     * @throws \Minz\Errors\ModelPropertyError if validator is declared but cannot
     *                                         be called
     */
    public function __construct($property_declarations = [])
    {
        $validated_property_declarations = [];

        foreach ($property_declarations as $property => $declaration) {
            if (!is_array($declaration)) {
                $declaration = ['type' => $declaration];
            }

            $declaration = array_merge(
                self::DEFAULT_PROPERTY_DECLARATION,
                $declaration
            );

            if (!in_array($declaration['type'], self::VALID_PROPERTY_TYPES)) {
                throw new Errors\ModelPropertyError(
                    $property,
                    Errors\ModelPropertyError::PROPERTY_TYPE_INVALID,
                    "`{$declaration['type']}` is not a valid property type."
                );
            }

            if (
                $declaration['validator'] !== null &&
                !is_callable($declaration['validator'])
            ) {
                throw new Errors\ModelPropertyError(
                    $property,
                    Errors\ModelPropertyError::PROPERTY_VALIDATOR_INVALID,
                    "`{$declaration['validator']}` validator cannot be called."
                );
            }

            $validated_property_declarations[$property] = $declaration;
            $this->$property = null;
        }

        $this->property_declarations = $validated_property_declarations;
    }

    /**
     * @return array
     */
    public function propertyDeclarations()
    {
        return $this->property_declarations;
    }

    /**
     * Return the list of declared properties values.
     *
     * Note that datetime are converted to timestamps.
     *
     * @return array
     */
    public function toValues()
    {
        $values = [];
        foreach ($this->property_declarations as $property => $declaration) {
            if ($declaration['type'] === 'datetime' && $this->$property) {
                // @todo Return ISO8601 string
                $values[$property] = $this->$property->getTimestamp();
            } else {
                $values[$property] = $this->$property;
            }
        }
        return $values;
    }

    /**
     * Load the properties values to the model.
     *
     * The array can contain strings, the values are automatically casted to
     * the correct type, based on the properties declarations.
     *
     * @param array $values
     *
     * @throws \Minz\Errors\ModelPropertyError if required property is missing
     * @throws \Minz\Errors\ModelPropertyError if property is not declared
     * @throws \Minz\Errors\ModelPropertyError if value doesn't correspond to the
     *                                         declared type
     * @throws \Minz\Errors\ModelPropertyError if validator returns false or a
     *                                         custom message
     */
    public function fromValues($values)
    {
        foreach ($this->property_declarations as $property => $declaration) {
            if (
                $declaration['required'] &&
                !isset($values[$property])
            ) {
                throw new Errors\ModelPropertyError(
                    $property,
                    Errors\ModelPropertyError::PROPERTY_REQUIRED,
                    "Required `{$property}` property is missing."
                );
            }
        }

        foreach ($values as $property => $value) {
            if (!isset($this->property_declarations[$property])) {
                throw new Errors\ModelPropertyError(
                    $property,
                    Errors\ModelPropertyError::PROPERTY_UNDECLARED,
                    "`{$property}` property has not been declared."
                );
            }

            $declaration = $this->property_declarations[$property];

            if ($value !== null) {
                if (
                    $declaration['type'] === 'integer' &&
                    filter_var($value, FILTER_VALIDATE_INT) === false
                ) {
                    throw new Errors\ModelPropertyError(
                        $property,
                        Errors\ModelPropertyError::VALUE_TYPE_INVALID,
                        "`{$property}` property must be an integer."
                    );
                }

                if (
                    $declaration['type'] === 'datetime' &&
                    filter_var($value, FILTER_VALIDATE_INT) === false
                ) {
                    throw new Errors\ModelPropertyError(
                        $property,
                        Errors\ModelPropertyError::VALUE_TYPE_INVALID,
                        "`{$property}` property must be a timestamp."
                    );
                }

                if (
                    $declaration['type'] === 'boolean' &&
                    filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null
                ) {
                    throw new Errors\ModelPropertyError(
                        $property,
                        Errors\ModelPropertyError::VALUE_TYPE_INVALID,
                        "`{$property}` property must be a boolean."
                    );
                }

                if ($declaration['type'] === 'datetime') {
                    $date = new \DateTime();
                    $date->setTimestamp(intval($value));
                    $value = $date;
                } elseif ($declaration['type'] === 'integer') {
                    $value = intval($value);
                } elseif ($declaration['type'] === 'boolean') {
                    $value = $value === 'true';
                }
            }

            $this->setProperty($property, $value);
        }
    }

    /**
     * Load a specific property value to the model.
     *
     * Note that values are NOT casted so you have to make sure to use the
     * correct type.
     *
     * @param array $values
     *
     * @throws \Minz\Errors\ModelPropertyError if property is not declared
     * @throws \Minz\Errors\ModelPropertyError if required property is null
     * @throws \Minz\Errors\ModelPropertyError if validator returns false or a
     *                                         custom message
     */
    public function setProperty($property, $value)
    {
        if (!isset($this->property_declarations[$property])) {
            throw new Errors\ModelPropertyError(
                $property,
                Errors\ModelPropertyError::PROPERTY_UNDECLARED,
                "`{$property}` property has not been declared."
            );
        }

        $declaration = $this->property_declarations[$property];

        if ($declaration['required'] && $value === null) {
            throw new Errors\ModelPropertyError(
                $property,
                Errors\ModelPropertyError::PROPERTY_REQUIRED,
                "Required `{$property}` property is missing."
            );
        }

        if ($value !== null && $declaration['validator']) {
            $validator_result = $declaration['validator']($value);

            if ($validator_result !== true) {
                $custom_message = '';
                if ($validator_result !== false) {
                    $custom_message = ': ' . $validator_result;
                }
                $error_message = "`{$property}` property is invalid ({$value}){$custom_message}.";
                throw new Errors\ModelPropertyError(
                    $property,
                    Errors\ModelPropertyError::VALUE_INVALID,
                    $error_message
                );
            }
        }

        $this->$property = $value;
    }
}
