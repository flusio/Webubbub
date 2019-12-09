<?php

namespace Minz;

/**
 * The Request class represents the request of a user. It contains basically
 * some headers, and GET and/or POST parameters.
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

    /**
     * Create a Request by loading from the $_SERVER variable.
     */
    public function __construct()
    {
        $this->method = strtolower($_SERVER['REQUEST_METHOD']);

        $url_components = parse_url($_SERVER['REQUEST_URI']);
        $this->path = $url_components['path'];
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
}
