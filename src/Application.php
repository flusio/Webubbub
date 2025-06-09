<?php

namespace Webubbub;

use Minz\Request;

/**
 * @phpstan-import-type ResponseReturnable from \Minz\Response
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Application
{
    /**
     * @return ResponseReturnable
     */
    public function run(Request $request): mixed
    {
        if ($request->method() === 'CLI') {
            $this->initCli($request);
        } else {
            $this->initApp($request);
        }

        return \Minz\Engine::run($request);
    }

    private function initApp(Request $request): void
    {
        $router = Router::loadApp();

        \Minz\Engine::init($router, [
            'start_session' => false,
            'not_found_view_pointer' => 'not_found.phtml',
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
            'controller_namespace' => '\\Webubbub\\controllers',
        ]);
    }

    private function initCli(Request $request): void
    {
        $router = Router::loadCli();

        $bin = $request->parameters->getString('bin', '');
        $bin = $bin === 'cli' ? 'php cli' : $bin;

        $current_command = $request->path();
        $current_command = trim(str_replace('/', ' ', $current_command));

        \Minz\Engine::init($router, [
            'start_session' => false,
            'not_found_view_pointer' => 'cli/not_found.txt',
            'internal_server_error_view_pointer' => 'cli/internal_server_error.txt',
            'controller_namespace' => '\\Webubbub\\cli',
        ]);

        \Minz\Template\Simple::addGlobals([
            'error' => null,
            'bin' => $bin,
            'current_command' => $current_command,
        ]);
    }
}
