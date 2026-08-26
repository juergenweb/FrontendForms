<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\EmailHelper;
use FrontendForms\EmailLogic;
use PHPUnit\Framework\TestCase;
use ProcessWire\User;

/**
 * Unit tests for EmailLogic::isEmailUnique().
 *
 * Uses mock objects for EmailHelper and User to isolate
 * the validation logic from ProcessWire dependencies.
 */
final class EmailLogicTest extends TestCase
{
    /**
     * Build a mock User with a configurable login state and email address.
     *
     * @param bool   $loggedIn Whether the user is considered logged in.
     * @param string $email    The email address to assign to the mock user.
     *
     * @return User
     */
    private function createUser(bool $loggedIn, string $email): User
    {
        $user = $this->createMock(User::class);

        $user->method('isLoggedin')
            ->willReturn($loggedIn);

        $user->email = $email;

        return $user;
    }

    /**
     * Build an EmailLogic instance whose currentUser() returns the given mock user.
     *
     * @param EmailHelper $helper Pre-configured EmailHelper mock.
     * @param User        $user   Mock user returned by currentUser().
     *
     * @return EmailLogic
     */
    private function createLogic(EmailHelper $helper, User $user): EmailLogic
    {
        return new class($helper, $user) extends EmailLogic {
            private User $mockUser;

            public function __construct(EmailHelper $helper, User $user)
            {
                parent::__construct($helper);
                $this->mockUser = $user;
            }

            protected function currentUser(): User
            {
                return $this->mockUser;
            }
        };
    }

    /**
     * 1) Verifies that an empty email always returns true.
     */
    public function testEmptyEmailReturnsTrue(): void
    {
        $helper = $this->createMock(EmailHelper::class);
        $user = $this->createUser(true, 'test@example.com');

        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isEmailUnique('', '', [], [])
        );
    }

    /**
     * 2) Verifies that email belongs to current logged-in user returns true.
     */
    public function testReturnsTrueIfUserAlreadyOwnsEmail(): void
    {
        $helper = $this->createMock(EmailHelper::class);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedin', '__get'])
            ->getMock();

        $user->method('isLoggedin')
            ->willReturn(true);

        $user->method('__get')
            ->willReturnMap([
                ['email', 'john@example.com'],
            ]);

        $logic = new class($helper) extends EmailLogic {
            public User $mockUser;

            protected function currentUser(): User
            {
                return $this->mockUser;
            }
        };

        $logic->mockUser = $user;

        $this->assertTrue(
            $logic->isEmailUnique(
                'email',
                'john@example.com',
                [],
                []
            )
        );
    }

    /**
     * 3) Verifies that an email already in the database causes the check to return false.
     */
    public function testReturnsFalseIfEmailExists(): void
    {
        $helper = $this->createMock(EmailHelper::class);
        $helper->expects($this->once())
            ->method('emailExists')
            ->with('john@example.com')
            ->willReturn(true);

        $user = $this->createUser(false, 'other@example.com');
        $logic = $this->createLogic($helper, $user);

        $this->assertFalse(
            $logic->isEmailUnique(
                'email',
                'john@example.com',
                [],
                []
            )
        );
    }

    /**
     * 4) Verifies that a non-existing email returns true.
     */
    public function testReturnsTrueIfEmailDoesNotExist(): void
    {
        $helper = $this->createMock(EmailHelper::class);
        $helper->expects($this->once())
            ->method('emailExists')
            ->with('john@example.com')
            ->willReturn(false);

        $user = $this->createUser(false, 'other@example.com');
        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isEmailUnique(
                'email',
                'john@example.com',
                [],
                []
            )
        );
    }

    /**
     * 5) Verifies that email comparison is trimmed correctly.
     */
    public function testEmailIsTrimmedBeforeValidation(): void
    {
        $helper = $this->createMock(EmailHelper::class);
        $helper->expects($this->once())
            ->method('emailExists')
            ->with('john@example.com')
            ->willReturn(false);

        $user = $this->createUser(false, 'other@example.com');
        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isEmailUnique(
                'email',
                '  john@example.com  ',
                [],
                []
            )
        );
    }

    /**
     * 6) Verifies that an email with different casing is treated as a distinct value from the current user's email.
     */
    public function testEmailComparisonIsCaseSensitive(): void
    {
        $helper = $this->createMock(EmailHelper::class);
        $helper->expects($this->once())
            ->method('emailExists')
            ->with('John@Example.com')
            ->willReturn(false);

        $user = $this->createUser(false, 'john@example.com');
        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isEmailUnique(
                'email',
                'John@Example.com',
                [],
                []
            )
        );
    }

    /**
     * 7) Verifies that a logged-in user with matching email bypasses database check.
     */
    public function testLoggedInUserBypassesEmailExistsCheck(): void
    {
        $helper = $this->createMock(EmailHelper::class);

        $helper->expects($this->never())
            ->method('emailExists');

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedin', '__get'])
            ->getMock();

        $user->method('isLoggedin')->willReturn(true);

        $user->method('__get')
            ->with('email')
            ->willReturn('john@example.com');

        $logic = new class($helper) extends EmailLogic {
            public User $mockUser;

            protected function currentUser(): User
            {
                return $this->mockUser;
            }
        };

        $logic->mockUser = $user;

        $this->assertTrue(
            $logic->isEmailUnique(
                'email',
                'john@example.com',
                [],
                []
            )
        );
    }

    /**
     * 8) Verifies that a whitespace-only email value is treated as empty.
     */
    public function testWhitespaceOnlyEmailReturnsTrue(): void
    {
        $helper = $this->createMock(EmailHelper::class);
        $helper->expects($this->never())
            ->method('emailExists');

        $user = $this->createUser(false, '');
        $logic = $this->createLogic($helper, $user);

        $this->assertTrue(
            $logic->isEmailUnique(
                'email',
                '   ',
                [],
                []
            )
        );
    }
}