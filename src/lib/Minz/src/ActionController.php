<?php

namespace Minz;

/**
 * Represent an action to execute within a controller.
 *
 * Actions are the core of Minz. They manage and coordinate models and
 * determinate what should be returned to the users. They take a Request as an
 * input and return a Response. They are contained within controllers files to
 * organized the logic of the application.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ActionController
{
    /** @var string */
    private $controller_name;

    /** @var string */
    private $action_name;

    /**
     * @param string $to A string representation of controller#action
     */
    public function __construct($to)
    {
        list($controller_name, $action_name) = explode('#', $to);

        $this->controller_name = $controller_name;
        $this->action_name = $action_name;
    }

    /**
     * @return string The name of the action's controller
     */
    public function controllerName()
    {
        return $this->controller_name;
    }

    /**
     * @return string The name of the action to execute
     */
    public function actionName()
    {
        return $this->action_name;
    }

    /**
     * Call the controller's action, passing a request in parameter and
     * returning a response for the user.
     *
     * @param \Minz\Request $request A request against which the action must be executed
     *
     * @throws \Minz\Errors\ControllerError if the controller's file cannot be loaded
     * @throws \Minz\Errors\ActionError if the action cannot be called
     * @throws \Minz\Errors\ActionError if the action doesn't return a Response
     *
     * @return \Minz\Response The response to return to the user
     */
    public function execute($request)
    {
        $included = self::loadControllerCode($this->controller_name);
        if (!$included) {
            $controller_filepath = "src/{$this->controller_name}.php";
            throw new Errors\ControllerError(
                "{$controller_filepath} file cannot be loaded."
            );
        }

        $app_name = Configuration::$app_name;
        $base_action = "\\{$app_name}\\controllers";
        $action = "{$base_action}\\{$this->controller_name}\\{$this->action_name}";
        if (!is_callable($action)) {
            throw new Errors\ActionError(
                "{$action} action cannot be called."
            );
        }

        $response = $action($request);
        if (!($response instanceof Response)) {
            throw new Errors\ActionError(
                "{$action} action does not return a Response."
            );
        }

        return $response;
    }

    /**
     * Include the controller file based on the given name.
     *
     * @param string $controller_name
     *
     * @return boolean Return true if the controller has been included, false otherwise
     */
    public static function loadControllerCode($controller_name)
    {
        $app_path = Configuration::$app_path;
        $controller_filepath = "{$app_path}/src/{$controller_name}.php";

        return @include_once($controller_filepath);
    }
}
