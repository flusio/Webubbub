<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class CSRFTest extends TestCase
{
    public function tearDown(): void
    {
        session_unset();
    }

    public function testGenerateToken()
    {
        $csrf = new CSRF();

        $token = $csrf->generateToken();

        $this->assertSame($_SESSION['CSRF'], $token);
    }

    public function testGenerateTokenTwiceDoesntChange()
    {
        $csrf = new CSRF();

        $first_token = $csrf->generateToken();
        $second_token = $csrf->generateToken();

        $this->assertSame($first_token, $second_token);
    }

    public function testGenerateTokenWhenTokenIsSetToEmpty()
    {
        $_SESSION['CSRF'] = '';
        $csrf = new CSRF();

        $token = $csrf->generateToken();

        $this->assertNotEmpty($_SESSION['CSRF']);
        $this->assertSame($_SESSION['CSRF'], $token);
    }

    public function testValidateToken()
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $valid = $csrf->validateToken($token);

        $this->assertTrue($valid);
    }

    public function testValidateTokenWithEmptyToken()
    {
        $csrf = new CSRF();
        $_SESSION['CSRF'] = '';

        $valid = $csrf->validateToken('');

        $this->assertFalse($valid);
    }

    public function testValidateTokenWhenValidatingTwice()
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $csrf->validateToken($token);
        $valid = $csrf->validateToken($token);

        $this->assertTrue($valid);
    }

    public function testValidateTokenWhenTokenIsWrong()
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $valid = $csrf->validateToken('not the token');

        $this->assertFalse($valid);
    }

    public function testValidateTokenWhenValidatingAfterFirstWrongTry()
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $csrf->validateToken('not the token');
        $valid = $csrf->validateToken($token);

        $this->assertTrue($valid);
    }
}
