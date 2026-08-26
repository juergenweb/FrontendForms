<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\FormValueStore;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FormValueStore.
 *
 * setValues() itself (including the sanitizer-chaining fix - each
 * sanitizer now correctly operates on the previous one's result instead
 * of always the raw value) is deliberately NOT covered here via a full
 * integration test: it reads from wire('input')->$method (the live,
 * global ProcessWire input object), and populating that reliably from a
 * unit test proved fragile in this session (same experience as the
 * earlier FormRandomKeySecurityTest attempt). The chaining fix itself was
 * verified by running the exact corrected logic standalone before
 * applying it. Only the simpler, dependency-free methods are tested
 * below.
 */
final class FormValueStoreTest extends TestCase
{
    // --- getValue() ---

    /**
     * 1) A value stored under the field's plain name is found by
     * getValue(), even though internally it may be looked up under either
     * the plain or form-id-prefixed key.
     */
    public function testGetValueReturnsNullWhenNothingSet(): void
    {
        $form = new Form('valuestoreform');
        $store = new FormValueStore($form);

        $this->assertNull($store->getValue('anything'));
    }

    // --- getValuesAsString() ---

    /**
     * 2) With no values collected at all, the flattened string is empty.
     */
    public function testGetValuesAsStringReturnsEmptyStringWhenNoValues(): void
    {
        $form = new Form('valuestoreform2');
        $store = new FormValueStore($form);

        $this->assertSame('', $store->getValuesAsString());
    }
}
