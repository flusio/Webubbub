<?php

namespace Minz\Output;

use PHPUnit\Framework\TestCase;
use Minz\Errors;

class ViewTest extends TestCase
{
    public function testConstructor()
    {
        $view = new View('rabbits/items.phtml');

        $this->assertStringEndsWith('src/views/rabbits/items.phtml', $view->filepath());
        $this->assertSame('text/html', $view->contentType());
    }

    public function testConstructorFailsIfViewFileDoesntExist()
    {
        $this->expectException(Errors\ViewError::class);
        $this->expectExceptionMessage(
            'src/views/rabbits/missing.phtml file cannot be found.'
        );

        new View('rabbits/missing.phtml');
    }

    public function testConstructorFailsIfViewFileExtensionIsntSupported()
    {
        $this->expectException(Errors\ViewError::class);
        $this->expectExceptionMessage(
            'nope is not a supported view file extension.'
        );

        new View('rabbits/items.nope');
    }

    public function testRender()
    {
        $rabbits = [
            'Bugs',
            'Clémentine',
            'Jean-Jean',
        ];
        $view = new View('rabbits/items.phtml', ['rabbits' => $rabbits]);

        $output = $view->render();

        $this->assertStringContainsString("<h1>The rabbits</h1>\n", $output);
        $this->assertStringContainsString("Bugs", $output);
        $this->assertStringContainsString("Clémentine", $output);
        $this->assertStringContainsString("Jean-Jean", $output);
    }

    public function testDeclareDefaultVariables()
    {
        View::declareDefaultVariables([
            'title' => 'Hello Rabbits!',
        ]);

        $view = new View('default_variable.phtml');
        $output = $view->render();
        $this->assertStringContainsString("<h1>Hello Rabbits!</h1>\n", $output);
    }
}
