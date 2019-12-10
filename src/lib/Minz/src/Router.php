<?php

namespace Minz;

/**
 * The Router stores the different routes of the application and is responsible
 * of matching user request path with patterns.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Router
{
    /** @var string[] */
    public const VALID_HTTP_VERBS = ['get', 'post', 'patch', 'put', 'delete'];

    /**
     * @var string[][] Contains the routes of the application. First level is
     *                 indexed by http verbs (it is initialized in
     *                 constructor), second level is indexed by the paths and
     *                 values are the routes destinations.
     */
    private $routes = [];

    public function __construct()
    {
        foreach (self::VALID_HTTP_VERBS as $http_verb) {
            $this->routes[$http_verb] = [];
        }
    }

    /**
     * Register a new route in the router.
     *
     * The pattern must always start by a slash and represents an URI. It is a
     * simple pattern where you can precise a variable by starting a section of
     * the URI by `:`. For instance: a `/rabbits/42` path will match the
     * `/rabbits/:id` pattern. Sections are splitted on slashes.
     *
     * The action pointer represents a combination of a controller name and an
     * action name, separated by a hash. For instance, `rabbits#items` points
     * to the `items` action of the `rabbits` controller.
     *
     * Via can be a single value or an array of HTTP verbs. You are responsible
     * to make the distinction in the action, but note it's highly recommended
     * to point an action to a single HTTP verb.
     *
     * @param string $pattern The path pattern of the new route
     * @param string $action_pointer The destination of the route
     * @param string|string[] $via The http verb(s) of the route
     *
     * @throws \Minz\Errors\RoutingError if pattern is empty
     * @throws \Minz\Errors\RoutingError if pattern doesn't start by a slash
     * @throws \Minz\Errors\RoutingError if action_pointer is empty
     * @throws \Minz\Errors\RoutingError if action_pointer contains no hash
     * @throws \Minz\Errors\RoutingError if action_pointer contains more than one hash
     * @throws \Minz\Errors\RoutingError if via is empty
     * @throws \Minz\Errors\RoutingError if via is not a valid http verb (or
     *                                   contains an invalid one)
     *
     * @return void
     */
    public function addRoute($pattern, $action_pointer, $via)
    {
        if (!$pattern) {
            throw new Errors\RoutingError('Route "pattern" cannot be empty.');
        }

        if ($pattern[0] !== '/') {
            throw new Errors\RoutingError('Route "pattern" must start by a slash (/).');
        }

        if (!$action_pointer) {
            throw new Errors\RoutingError('Route "action_pointer" cannot be empty.');
        }

        if (strpos($action_pointer, '#') === false) {
            throw new Errors\RoutingError(
                'Route "action_pointer" must contain a hash (#).'
            );
        }

        if (substr_count($action_pointer, '#') > 1) {
            throw new Errors\RoutingError(
                'Route "action_pointer" must contain at most one hash (#).'
            );
        }

        if (!is_array($via)) {
            $via = [$via];
        }

        $via = array_filter($via);

        if (empty($via)) {
            throw new Errors\RoutingError('Route "via" cannot be empty.');
        }

        foreach ($via as $http_verb) {
            if (!in_array($http_verb, self::VALID_HTTP_VERBS)) {
                $verbs_as_string = implode(', ', self::VALID_HTTP_VERBS);
                throw new Errors\RoutingError(
                    "Route \"via\" must be a valid HTTP verb ({$verbs_as_string})."
                );
            }

            $this->routes[$http_verb][$pattern] = $action_pointer;
        }
    }

    /**
     * Return the matching action pointer for given request http verb and path.
     *
     * @param string $http_verb
     * @param string $path
     *
     * @throws \Minz\Errors\RoutingError if http verb is no a valid http verb
     * @throws \Minz\Errors\RouteNotFoundError if no patterns match with the path
     *
     * @return string The corresponding action pointer if any.
     */
    public function match($http_verb, $path)
    {
        if (!in_array($http_verb, self::VALID_HTTP_VERBS)) {
            $verbs_as_string = implode(', ', self::VALID_HTTP_VERBS);
            throw new Errors\RoutingError(
                "HTTP verb must be valid ({$verbs_as_string})."
            );
        }

        $http_verb_routes = $this->routes[$http_verb];
        foreach ($http_verb_routes as $pattern => $action_pointer) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                return $action_pointer;
            }
        }

        throw new Errors\RouteNotFoundError(
            "Path \"{$http_verb} {$path}\" doesn’t match any route."
        );
    }

    /**
     * @return string[][] The list of registered routes
     */
    public function routes()
    {
        return array_filter($this->routes);
    }

    /**
     * Check if a path matches with a pattern.
     *
     * @param string $path
     * @param string $pattern
     *
     * @return boolean Return true if the path matches with the pattern, or
     *                 false otherwise.
     */
    private function pathMatchesPattern($path, $pattern)
    {
        $pattern_exploded = explode('/', $pattern);
        $path_exploded = explode('/', $path);

        if (count($pattern_exploded) !== count($path_exploded)) {
            return false;
        }

        for ($i = 0; $i < count($pattern_exploded); $i++) {
            $pattern_element = $pattern_exploded[$i];
            $path_element = $path_exploded[$i];

            // In the pattern /rabbits/:id, :id is a variable name, which is
            // replaced by a real value in the URI (e.g. /rabbits/42).
            // We can't check the equality of :id and 42 but there are
            // "equivalent" from a routing point of view.
            $pattern_is_variable = $pattern_element && $pattern_element[0] === ':';
            if (!$pattern_is_variable && $pattern_element !== $path_element) {
                return false;
            }
        }

        return true;
    }
}
