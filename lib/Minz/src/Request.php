<?php

namespace Minz;

/**
 * The Request class represents the request of a user. It represents basically
 * some headers, and GET / POST parameters.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Request
{
    /** @var string */
    private $method;

    /** @var string */
    private $path;

    /** @var mixed[] */
    private $parameters;

    /**
     * Create a Request
     *
     * @param string $method Usually the method from $_SERVER['REQUEST_METHOD']
     * @param string $uri Usually the method from $_SERVER['REQUEST_URI']
     * @param mixed[] $parameters Usually a merged array of $_GET and $_POST
     */
    public function __construct($method, $uri, $parameters = [])
    {
        $method = strtolower($method);
        $uri_components = parse_url($uri);

        if (!in_array($method, Router::VALID_VIAS)) {
            $vias_as_string = implode(', ', Router::VALID_VIAS);
            throw new Errors\RequestError(
                "{$method} method is invalid ({$vias_as_string})."
            );
        }

        if (!$uri_components || !$uri_components['path']) {
            throw new Errors\RequestError("{$uri} URI path cannot be parsed.");
        }

        if ($uri_components['path'][0] !== '/') {
            throw new Errors\RequestError("{$uri} URI path must start with a slash.");
        }

        if (!is_array($parameters)) {
            throw new Errors\RequestError('Parameters are not in an array.');
        }

        $this->method = $method;
        $this->path = $uri_components['path'];
        $this->parameters = $parameters;
    }

    /**
     * @return string The HTTP method/verb of the user request
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * @return string The path of the request (without the query part, after
     *                the question mark)
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Return a parameter value from $_GET or $_POST.
     *
     * @param string $name The name of the parameter to get
     * @param mixed $default A default value to return if the parameter doesn't exist
     *
     * @return mixed
     */
    public function param($name, $default = null)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        } else {
            return $default;
        }
    }
}
