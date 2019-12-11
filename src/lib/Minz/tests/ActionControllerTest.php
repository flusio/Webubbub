<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ActionControllerTest extends TestCase
{
    public function testConstruct()
    {
        $action_controller = new ActionController('rabbits#items');

        $this->assertSame('rabbits', $action_controller->controllerName());
        $this->assertSame('items', $action_controller->actionName());
    }

    public function testExecute()
    {
        $request = new Request('GET', '/');
        $action_controller = new ActionController('rabbits#items');

        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
        $this->assertSame('rabbits#items.phtml', $response->viewPointer());
    }

    public function testExecuteFailsIfControllerDoesntExist()
    {
        $this->expectException(Errors\ControllerError::class);
        $this->expectExceptionMessage(
            'src/missing/controller.php file cannot be loaded.'
        );

        $request = new Request('GET', '/');
        $action_controller = new ActionController('missing#items');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfControllerPathIsDirectory()
    {
        $this->expectException(Errors\ControllerError::class);
        $this->expectExceptionMessage(
            'src/controller_as_directory/controller.php file cannot be loaded.'
        );

        $request = new Request('GET', '/');
        $action_controller = new ActionController('controller_as_directory#items');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfActionIsNotCallable()
    {
        $this->expectException(Errors\ActionError::class);
        $this->expectExceptionMessage(
            '\AppTest\controllers\rabbits\uncallable action cannot be called.'
        );

        $request = new Request('GET', '/');
        $action_controller = new ActionController('rabbits#uncallable');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfActionDoesNotReturnResponse()
    {
        $this->expectException(Errors\ActionError::class);
        $this->expectExceptionMessage(
            '\AppTest\controllers\rabbits\noResponse action does not return a Response.'
        );

        $request = new Request('GET', '/');
        $action_controller = new ActionController('rabbits#noResponse');

        $action_controller->execute($request);
    }
}
