<?php

namespace Minz\Errors;

/**
 * Exception raised for erroneous property declaration.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ModelPropertyError extends \LogicException
{
    public const PROPERTY_VALIDATOR_INVALID = 0;
    public const PROPERTY_TYPE_INVALID = 1;
    public const PROPERTY_REQUIRED = 2;
    public const PROPERTY_UNDECLARED = 3;
    public const VALUE_INVALID = 4;
    public const VALUE_TYPE_INVALID = 5;

    private $property;

    public function __construct($property, $code, $message)
    {
        $this->property = $property;
        parent::__construct($message, $code);
    }

    public function property()
    {
        return $this->property;
    }
}
