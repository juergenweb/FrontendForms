<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ProcessWire\InputfieldPassword;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * A minimal InputfieldPassword subclass with fully controlled, predictable
 * validation behavior, used instead of the real requirements-based
 * validation logic. This avoids depending on the exact format/meaning of
 * InputfieldPassword::$requirements (which isn't something this test
 * suite can verify without a live install), and - more importantly - lets
 * a single-character password like "0" pass validation, which is needed
 * to specifically exercise the array_filter() fix (a real-world password
 * policy would almost certainly reject a 1-character password on length
 * grounds anyway, which would mask whether array_filter() itself is
 * behaving correctly).
 *
 * NOTE: isValidPassword()'s parameter/return types are intentionally left
 * unspecified here (rather than guessed at) for maximum compatibility
 * with whatever the real parent signature turns out to be - if the real
 * InputfieldPassword::isValidPassword() declares stricter types, this
 * override may need its signature adjusted to match.
 */
class StubInputfieldPassword extends InputfieldPassword
{
    /** @var string[] Passwords (after cleanup) that should be treated as invalid, everything else is valid. */
    public array $rejectedPasswords = [];

    public function isValidPassword($password)
    {
        return !in_array($password, $this->rejectedPasswords, true);
    }

    public function getErrors($clear = false)
    {
        return [];
    }
}

/**
 * Unit tests for FrontendForms::cleanUpPasswordList().
 */
final class CleanUpPasswordListTest extends TestCase
{
    private function makeModule(array $rejectedPasswords = []): FrontendForms
    {
        $ref = new ReflectionClass(FrontendForms::class);
        /** @var FrontendForms $module */
        $module = $ref->newInstanceWithoutConstructor();

        $stub = new StubInputfieldPassword();
        $stub->rejectedPasswords = $rejectedPasswords;

        $prop = new ReflectionProperty(FrontendForms::class, 'password_object');
        $prop->setAccessible(true);
        $prop->setValue($module, $stub);

        return $module;
    }

    private function callCleanUpPasswordList(FrontendForms $module, string|array $data): string
    {
        $method = new ReflectionMethod(FrontendForms::class, 'cleanUpPasswordList');
        $method->setAccessible(true);
        return $method->invoke($module, $data);
    }

    /**
     * 1) Empty lines/entries are removed, valid passwords are kept.
     */
    public function testRemovesEmptyEntriesAndKeepsValidPasswords(): void
    {
        $module = $this->makeModule();

        $result = $this->callCleanUpPasswordList($module, "password123\n\nqwerty\n");

        $lines = explode("\n", $result);
        $this->assertContains('password123', $lines);
        $this->assertContains('qwerty', $lines);
        $this->assertNotContains('', $lines);
    }

    /**
     * 2) Whitespace-only entries are also removed, matching the intent of
     * "filter out empty values" (trim() based, not a bare falsy check).
     */
    public function testRemovesWhitespaceOnlyEntries(): void
    {
        $module = $this->makeModule();

        $result = $this->callCleanUpPasswordList($module, "password123\n   \nqwerty\n");

        $lines = array_filter(explode("\n", $result), fn ($l) => trim($l) !== '');
        $this->assertCount(2, $lines);
    }

    /**
     * 3) REGRESSION TEST for the fixed bug: a password entry consisting of
     * exactly "0" is not silently dropped by array_filter() before it even
     * reaches validation - it survives through to the final result (given
     * a validator, via the stub, that would otherwise accept it).
     */
    public function testDoesNotDropPasswordConsistingOfZero(): void
    {
        $module = $this->makeModule(); // nothing rejected - "0" should survive

        $result = $this->callCleanUpPasswordList($module, "password123\n0\nqwerty\n");

        $lines = explode("\n", $result);
        $this->assertContains('0', $lines);
    }

    /**
     * 4) Passwords that fail validation are removed from the result.
     */
    public function testRemovesPasswordsThatFailValidation(): void
    {
        $module = $this->makeModule(['weakpass']);

        $result = $this->callCleanUpPasswordList($module, "password123\nweakpass\nqwerty\n");

        $lines = explode("\n", $result);
        $this->assertNotContains('weakpass', $lines);
        $this->assertContains('password123', $lines);
        $this->assertContains('qwerty', $lines);
    }

    /**
     * 5) Dots, spaces, and other whitespace characters are stripped from
     * each password before validation/storage.
     */
    public function testStripsDotsSpacesAndWhitespaceFromEachPassword(): void
    {
        $module = $this->makeModule();

        $result = $this->callCleanUpPasswordList($module, "pass.word 123\n");

        $this->assertSame('password123', trim($result));
    }

    /**
     * 6) Duplicate entries (including ones that only become duplicates
     * after the dot/space cleanup) are removed.
     */
    public function testRemovesDuplicateEntriesAfterCleanup(): void
    {
        $module = $this->makeModule();

        // "pass.word" and "pass word" both clean down to "password"
        $result = $this->callCleanUpPasswordList($module, "pass.word\npass word\n");

        $lines = array_filter(explode("\n", $result), fn ($l) => trim($l) !== '');
        $this->assertCount(1, $lines);
    }

    /**
     * 7) An array input (not just a newline-separated string) is accepted
     * and processed the same way, per the string|array parameter type.
     */
    public function testAcceptsArrayInputDirectly(): void
    {
        $module = $this->makeModule();

        $result = $this->callCleanUpPasswordList($module, ['password123', '', 'qwerty']);

        $lines = explode("\n", $result);
        $this->assertContains('password123', $lines);
        $this->assertContains('qwerty', $lines);
    }

    /**
     * 8) A fully empty input results in an empty string, not an error.
     */
    public function testEmptyInputReturnsEmptyString(): void
    {
        $module = $this->makeModule();

        $result = $this->callCleanUpPasswordList($module, '');

        $this->assertSame('', $result);
    }
}
