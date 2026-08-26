<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\CustomRules;
use FrontendForms\Form;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for CustomRules.
 *
 * The constructor does real work (boots Valitron's language config, scans
 * the ValidationRules directory, and registers every found rule with
 * Valitron's global rule registry) - these tests are closer to
 * integration tests, verifying the end-to-end effect (a real, usable
 * Valitron rule) rather than internal implementation details.
 */
final class CustomRulesTest extends TestCase
{
    /**
     * 1) Construction with a real Form instance does not throw.
     */
    public function testConstructionDoesNotThrow(): void
    {
        $form = new Form('myform');

        new CustomRules($form);

        $this->addToAssertionCount(1);
    }

    /**
     * 2) After construction, a known custom rule ("compareTexts", defined
     * in TextRules) is actually registered and usable through a real
     * Valitron Validator - confirms the directory autoloader found and
     * registered the rule classes, not just that the constructor ran
     * without error.
     */
    public function testConstructionRegistersCustomRulesWithValitron(): void
    {
        $form = new Form('myform');
        new CustomRules($form);

        $v = new Validator(['answer' => 'blue']);
        $v->rule('compareTexts', 'answer', ['blue']);

        $this->assertTrue($v->validate());
    }

    /**
     * 3) The registered custom rule genuinely validates (not a no-op
     * always-pass registration) - an incorrect value fails.
     */
    public function testRegisteredCustomRuleGenuinelyValidates(): void
    {
        $form = new Form('myform');
        new CustomRules($form);

        $v = new Validator(['answer' => 'wrong']);
        $v->rule('compareTexts', 'answer', ['blue']);

        $this->assertFalse($v->validate());
    }
}
