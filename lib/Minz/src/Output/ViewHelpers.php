<?php

/**
 * These functions are mean to be used inside View files. They are declared in
 * global namespace so we don't have to declare the namespaces in views.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

/**
 * Alias for \Minz\Url::for
 *
 * @see \Minz\Url::for
 */
function url($action_pointer, $parameters = [])
{
    return \Minz\Url::for($action_pointer, $parameters = []);
}

/**
 * Alias for \Minz\Url::absoluteFor
 *
 * @see \Minz\Url::absoluteFor
 */
function url_full($action_pointer, $parameters = [])
{
    return \Minz\Url::absoluteFor($action_pointer, $parameters = []);
}

/**
 * Return the relative URL for a static file (under public folder)
 *
 * @param string $filename
 *
 * @return string
 */
function url_static($filename)
{
    $path = \Minz\Configuration::$url_options['path'];
    if (substr($path, -1) !== '/') {
        $path = $path . '/';
    }
    return $path . $filename;
}

/**
 * Return a CSRF token
 *
 * @return string
 */
function csrf_token()
{
    return (new \Minz\CSRF())->generateToken();
}
