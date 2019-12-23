<?php

namespace Minz;

/**
 * The Url class provides helper functions to build internal URLs.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Url
{
    /** @var \Minz\Router */
    private static $router;

    /**
     * Set the router so Url class can lookup for action pointers. It needs to
     * be called first.
     *
     * @param \Minz\Router $router
     */
    public static function setRouter($router)
    {
        self::$router = $router;
    }

    /**
     * Return the relative URL corresponding to an action.
     *
     * @param string $action_pointer
     * @param array $parameters
     *
     * @throws \Minz\Errors\UrlError if router has not been registered
     * @throws \Minz\Errors\UrlError if the action pointer has not be added to
     *                               the router
     * @throws \Minz\Errors\UrlError if required parameter is missing
     *
     * @return string The URL corresponding to the action
     */
    public static function for($action_pointer, $parameters = [])
    {
        if (!self::$router) {
            throw new Errors\UrlError(
                'You must set a Router to the Url class before using it.'
            );
        }

        $vias = Router::VALID_VIAS;
        foreach ($vias as $via) {
            try {
                return self::$router->uriFor($via, $action_pointer, $parameters);
            } catch (Errors\RouteNotFoundError $e) {
                // Do nothing on purpose
            } catch (Errors\RoutingError $e) {
                throw new Errors\UrlError($e->getMessage());
            }
        }

        throw new Errors\UrlError(
            "{$action_pointer} action pointer does not exist in the router."
        );
    }

    /**
     * Return the absolute URL corresponding to an action.
     *
     * @param string $action_pointer
     * @param array $parameters
     *
     * @throws \Minz\Errors\UrlError if router has not been registered
     * @throws \Minz\Errors\UrlError if the action pointer has not be added to
     *                               the router
     * @throws \Minz\Errors\UrlError if required parameter is missing
     *
     * @return string The URL corresponding to the action
     */
    public static function absoluteFor($action_pointer, $parameters = [])
    {
        $url_options = Configuration::$url_options;

        $relative_url = self::for($action_pointer, $parameters);
        $absolute_url = $url_options['protocol'] . '://';
        $absolute_url .= $url_options['host'];
        if (
            !($url_options['protocol'] === 'https' && $url_options['port'] === 443) &&
            !($url_options['protocol'] === 'http' && $url_options['port'] === 80)
        ) {
            $absolute_url .= ':' . $url_options['port'];
        }
        $absolute_url .= $relative_url;

        return $absolute_url;
    }
}
