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
        $request = new Request();
        $action_controller = new ActionController('rabbits#items');

        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
        $this->assertSame('rabbits/items.phtml', $response->viewFilename());
    }

    public function testExecuteFailsIfControllerDoesntExist()
    {
        $this->expectException(Errors\ControllerError::class);
        $this->expectExceptionMessage(
            'tests/fixtures/controllers/missing.php file cannot be found.'
        );

        $request = new Request();
        $action_controller = new ActionController('missing#items');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfActionIsNotCallable()
    {
        $this->expectException(Errors\ActionError::class);
        $this->expectExceptionMessage(
            '\AppTest\controllers\rabbits\uncallable action cannot be called.'
        );

        $request = new Request();
        $action_controller = new ActionController('rabbits#uncallable');

        $action_controller->execute($request);
    }
}
