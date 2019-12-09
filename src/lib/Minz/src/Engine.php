<?php

namespace Minz;

/**
 * Coordinate the different parts of the framework core.
 *
 * The engine is responsible to coordinate a request with a router, in order to
 * return a response to the user, based on the logic of the application's
 * actions.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Engine
{
    /** @var \Minz\Router */
    private $router;

    /**
     * @param \Minz\Router $router The router to use in the application
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * This method always return a response to the user. If an error happens in
     * the logic of the application, the adequate HTTP code with a pertinent
     * view.
     *
     * @param \Minz\Request $request The actual request from the user.
     *
     * @return \Minz\Response A response to return to the user.
     */
    public function run($request)
    {
        try {
            $to = $this->router->match($request->method(), $request->path());
        } catch (Errors\RouteNotFoundError $e) {
            return Response::notFound('errors#not_found.phtml', ['error' => $e]);
        }

        $action_controller = new ActionController($to);
        try {
            return $action_controller->execute($request);
        } catch (Errors\ControllerError | Errors\ActionError | Errors\ResponseError $e) {
            return Response::internalServerError(
                'errors#internal_server_error.phtml',
                ['error' => $e]
            );
        }
    }
}
