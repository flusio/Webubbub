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
     * @throws \Minz\Errors\ControllerError if the controller's file cannot be found
     * @throws \Minz\Errors\ActionError if the action cannot be called
     *
     * @return \Minz\Response The response to return to the user
     */
    public function execute($request)
    {
        $app_name = Configuration::$app_name;
        $app_path = Configuration::$app_path;
        $controllers_path = Configuration::$controllers_path;

        $controller_filepath = "{$controllers_path}/{$this->controller_name}.php";
        $controller_app_filepath = "{$app_path}/{$controller_filepath}";
        if (!file_exists($controller_app_filepath)) {
            throw new Errors\ControllerError(
                "{$controller_filepath} file cannot be found."
            );
        }

        require_once($controller_app_filepath);

        $base_action = "\\{$app_name}\\controllers";
        $action = "{$base_action}\\{$this->controller_name}\\{$this->action_name}";
        if (!is_callable($action)) {
            throw new Errors\ActionError(
                "{$action} action cannot be called."
            );
        }

        return $action($request);
    }
}
