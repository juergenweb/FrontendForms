<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\UsernameHelper;
use FrontendForms\UsernameLogic;
use PHPUnit\Framework\TestCase;
use ProcessWire\User;

/**
 * Unit tests for UsernameLogic validation methods.
 *
 * Covers: isUsernameUnique (ownership, existence, normalization)
 * and isValidUsernameSyntax (length, allowed characters, edge cases).
 */
final class UsernameLogicTest extends TestCase
{
    private UsernameLogic $logic;

    /*
    |--------------------------------------------------------------------------
    | Rule: uniqueUsername
    | Method: isUsernameUnique
    |--------------------------------------------------------------------------
    */

    /**
     * Create a UsernameLogic instance shared across all tests.
     */
    protected function setUp(): void
    {
        $usernameHelper = new UsernameHelper();

        $this->logic = new UsernameLogic(
            $usernameHelper
        );

        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('test');

        $this->logic->setForm($form);
        $usernameHelper->setForm($form);
    }

    /**
     * Build a mock User with a configurable login state and username.
     *
     * @param bool   $loggedIn Whether the user is considered logged in.
     * @param string $name     The username to assign to the mock user.
     *
     * @return User
     */
    private function createUser(bool $loggedIn, string $name): User
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedin', '__get'])
            ->getMock();

        $user->method('isLoggedin')
            ->willReturn($loggedIn);

        $user->method('__get')
            ->willReturnMap([
                ['name', $name],
            ]);

        return $user;
    }

    /**
     * Build a UsernameLogic instance whose currentUser() returns the given mock user.
     *
     * @param UsernameHelper $helper Pre-configured UsernameHelper mock.
     * @param User           $user   Mock user returned by currentUser().
     *
     * @return UsernameLogic
     */
    private function createLogic(
        UsernameHelper $helper,
        User $user
    ): UsernameLogic {
        return new class($helper, $user) extends UsernameLogic {

            private User $mockUser;

            public function __construct(
                UsernameHelper $helper,
                User $user
            ) {
                parent::__construct($helper);
                $this->mockUser = $user;
            }

            /** @noinspection PhpUnused */
            protected function currentUser(): User
            {
                return $this->mockUser;
            }
        };
    }

    /**
     * 2) Validate that current user can keep their username.
     */
    public function testReturnsTrueIfUserAlreadyOwnsUsername(): void
    {
        $helper = $this->createMock(UsernameHelper::class);

        $helper->method('normalizeUsername')
            ->willReturn('john');

        $user = $this->createUser(true, 'john');

        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isUsernameUnique('username', 'john', [], [])
        );
    }

    /**
     * 3) Validate that existing username returns false.
     */
    public function testReturnsFalseIfUsernameAlreadyExists(): void
    {
        $helper = $this->createMock(UsernameHelper::class);

        $helper->method('normalizeUsername')
            ->willReturn('john');

        $helper->method('usernameExists')
            ->with('john')
            ->willReturn(true);

        $user = $this->createUser(false, 'other');

        $logic = $this->createLogic($helper, $user);

        $this->assertFalse(
            $logic->isUsernameUnique('username', 'john', [], [])
        );
    }

    /**
     * 4) Validate that non-existing username returns true.
     */
    public function testReturnsTrueIfUsernameDoesNotExist(): void
    {
        $helper = $this->createMock(UsernameHelper::class);

        $helper->method('normalizeUsername')
            ->willReturn('john');

        $helper->method('sanitizeUsername')
            ->willReturn('john');

        $helper->method('usernameExists')
            ->willReturn(false);

        $user = $this->createUser(false, 'other');

        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isUsernameUnique('username', 'john', [], [])
        );
    }

    /**
     * 5) Validate that username normalization is applied.
     */
    public function testUsernameIsNormalizedBeforeValidation(): void
    {
        $helper = $this->createMock(UsernameHelper::class);

        $helper->expects($this->once())
            ->method('normalizeUsername')
            ->with('  john  ')
            ->willReturn('john');

        $helper->method('sanitizeUsername')
            ->willReturn('john');

        $helper->method('usernameExists')
            ->willReturn(false);

        $user = $this->createUser(false, 'other');

        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isUsernameUnique('username', '  john  ', [], [])
        );
    }

    /**
     * 6) Validate that sanitization is applied before username lookup.
     */
    public function testUsernameIsSanitizedBeforeLookup(): void
    {
        $helper = $this->createMock(UsernameHelper::class);

        $helper->method('normalizeUsername')
            ->willReturn('john');

        $helper->expects($this->once())
            ->method('usernameExists')
            ->with('john')
            ->willReturn(false);

        $user = $this->createUser(false, 'different-user');

        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isUsernameUnique('username', 'john', [], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: usernameSyntax
    | Method: isValidUsernameSyntax
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that a valid username is accepted.
     */
    public function testValidUsernameReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'john_doe',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that uppercase letters are normalized and accepted.
     */
    public function testUsernameWithUppercaseLettersReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'John_Doe',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that leading and trailing spaces are trimmed.
     */
    public function testUsernameWithLeadingAndTrailingSpacesReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            '  john_doe  ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a username with the minimum allowed length is accepted.
     */
    public function testMinimumLengthUsernameReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'abc',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a username with the maximum allowed length is accepted.
     */
    public function testMaximumLengthUsernameReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            str_repeat('a', 30),
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a username shorter than the minimum length is rejected.
     */
    public function testUsernameTooShortReturnsFalse(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'ab',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that a username longer than the maximum length is rejected.
     */
    public function testUsernameTooLongReturnsFalse(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            str_repeat('a', 31),
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that usernames containing dots are accepted.
     */
    public function testUsernameWithDotsReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'john.doe',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that usernames containing underscores are accepted.
     */
    public function testUsernameWithUnderscoresReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'john_doe',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that usernames containing hyphens are accepted.
     */
    public function testUsernameWithHyphensReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'john-doe',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that usernames with mixed valid characters are accepted.
     */
    public function testUsernameWithMixedValidCharactersReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'user-123_test.name',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that numeric usernames are accepted.
     */
    public function testNumericUsernameReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            '12345',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that usernames containing spaces are rejected.
     */
    public function testUsernameWithSpacesInsideReturnsFalse(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'john doe',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that usernames containing special characters are rejected.
     */
    public function testUsernameWithSpecialCharacterReturnsFalse(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'john@doe',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that usernames containing non-ASCII characters are rejected.
     */
    public function testUsernameWithUmlautReturnsFalse(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            'jörg',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that an empty username is treated as empty form field and returns true.
     */
    public function testEmptyUsernameReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a null value is treated as an empty input field and returns true.
     */
    public function testNullUsernameReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            null,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that an integer value is normalized and handled correctly.
     */
    public function testIntegerUsernameReturnsTrue(): void
    {
        $result = $this->logic->isValidUsernameSyntax(
            'username',
            123,
            [],
            []
        );

        $this->assertTrue($result);
    }
}