<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\ValitronAPI;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ValitronAPI.
 */
final class ValitronAPITest extends TestCase
{
    // --- setValidator() / getValidator() ---

    /**
     * 1) A validator name set via setValidator() is returned unchanged by
     * getValidator().
     */
    public function testSetAndGetValidator(): void
    {
        $api = new ValitronAPI();
        $api->setValidator('required');

        $this->assertSame('required', $api->getValidator());
    }

    /**
     * 2) Before setValidator() is ever called, getValidator() returns an
     * empty string.
     */
    public function testGetValidatorDefaultsToEmptyString(): void
    {
        $api = new ValitronAPI();

        $this->assertSame('', $api->getValidator());
    }

    // --- setRule() ---

    /**
     * 3) setRule() returns an array with the validator name and the given
     * options.
     */
    public function testSetRuleReturnsNameAndOptions(): void
    {
        $api = new ValitronAPI();

        $result = $api->setRule('lengthMin', [5]);

        $this->assertSame(['name' => 'lengthMin', 'options' => [5]], $result);
    }

    /**
     * 4) setRule() defaults to an empty options array when none is given.
     */
    public function testSetRuleDefaultsToEmptyOptions(): void
    {
        $api = new ValitronAPI();

        $result = $api->setRule('required');

        $this->assertSame(['name' => 'required', 'options' => []], $result);
    }

    /**
     * 5) setRule() trims surrounding whitespace from the validator name.
     */
    public function testSetRuleTrimsValidatorName(): void
    {
        $api = new ValitronAPI();

        $result = $api->setRule('  required  ');

        $this->assertSame('required', $result['name']);
    }
}
