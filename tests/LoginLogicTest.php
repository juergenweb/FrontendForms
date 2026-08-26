<?php

declare(strict_types=1);

namespace Tests;

use FrontendForms\FieldNameResolverHelper;
use FrontendForms\LoginHelper;
use FrontendForms\LoginLogic;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ProcessWire\Module;
use ProcessWire\User;
use stdClass;

/**
 * Unit tests for LoginLogic validation methods.
 *
 * Covers: username/password matching, email/password matching,
 * and two-factor authentication code verification.
 */
final class LoginLogicTest extends TestCase
{
    /**
     * Creates a LoginLogic instance with a mocked helper.
     *
     * @param bool $loginValid Whether login validation should succeed.
     *
     * @return LoginLogic
     */
    private function makeLogic(bool $loginValid = true): LoginLogic
    {
        $helper = $this->createMock(LoginHelper::class);

        $helper->method('validateLogin')
            ->willReturn($loginValid);

        $fieldNameResolver = new FieldNameResolverHelper();

        if (!$loginValid) {
            $helper->expects($this->once())
                ->method('createSessionForLoginAttempts');
        }

        return new LoginLogic($helper, $fieldNameResolver);
    }

    /*
    |--------------------------------------------------------------------------
    | Username / Password validation
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that username validation throws an exception
     * when required field name is missing.
     */
    public function testUsernameRuleThrowsExceptionWithoutFieldname(): void
    {
        $logic = $this->makeLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->isValidPasswordUsernameMatch(
            'password',
            'secret',
            [],
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Email / Password validation
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that email validation throws an exception
     * when required field name is missing.
     */
    public function testEmailRuleThrowsExceptionWithoutFieldname(): void
    {
        $logic = $this->makeLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->isValidPasswordEmailMatch(
            'password',
            'secret',
            [],
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TFA validation
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that TFA validation returns false
     * when no parameters are provided.
     */
    public function testTfaCodeReturnsFalseWhenParametersAreMissing(): void
    {
        $logic = $this->makeLogic();

        $this->assertFalse(
            $logic->isValidTfaCode(
                'tfa',
                '123456',
                [],
                []
            )
        );
    }

    /**
     * Verifies that TFA validation returns false
     * when first parameter is not a valid User instance.
     */
    public function testTfaCodeReturnsFalseWhenUserIsInvalid(): void
    {
        $logic = $this->makeLogic();

        $module = $this->createMock(Module::class);

        $this->assertFalse(
            $logic->isValidTfaCode(
                'tfa',
                '123456',
                ['not-a-user', $module],
                []
            )
        );
    }

    /**
     * Verifies that TFA validation returns false
     * when second parameter is not a valid Module instance.
     */
    public function testTfaCodeReturnsFalseWhenModuleIsInvalid(): void
    {
        $logic = $this->makeLogic();

        $user = $this->createMock(User::class);

        $this->assertFalse(
            $logic->isValidTfaCode(
                'tfa',
                '123456',
                [$user, new stdClass()],
                []
            )
        );
    }

    /**
     * Verifies that TFA validation returns false
     * when module is not whitelisted.
     */
    public function testTfaCodeReturnsFalseForNotWhitelistedModule(): void
    {
        $logic = $this->makeLogic();

        $user = $this->createMock(User::class);
        $module = $this->createMock(Module::class);

        $module->method('className')
            ->with(true)
            ->willReturn('Some\\OtherModule');

        $this->assertFalse(
            $logic->isValidTfaCode(
                'tfa',
                '123456',
                [$user, $module],
                []
            )
        );
    }

    /**
     * Verifies that TFA validation returns false
     * when module throws an exception during verification.
     */
    public function testTfaCodeReturnsFalseWhenModuleThrowsException(): void
    {
        $logic = $this->makeLogic();

        $user = $this->createMock(User::class);
        $module = $this->createMock(Module::class);

        $module->method('className')
            ->with(true)
            ->willReturn('ProcessWire\\TfaTotp');

        $this->assertFalse(
            $logic->isValidTfaCode(
                'tfa',
                '123456',
                [$user, $module],
                []
            )
        );
    }
}