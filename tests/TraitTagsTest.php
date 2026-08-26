<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Tag;
use FrontendForms\TraitTags;
use PHPUnit\Framework\TestCase;

/**
 * A minimal Tag subclass using TraitTags, for testing the trait in
 * isolation rather than through Label (which layers its own asterisk/
 * required logic on top).
 */
final class ConcreteTaggedElement extends Tag
{
    use TraitTags;

    public function __construct(string $id)
    {
        parent::__construct($id);
    }

    /**
     * Simulates what Label::__construct() now correctly does: set the
     * element's own default tag via parent::setTag(), bypassing the
     * trait's customTag-recording override - this must NOT register as a
     * "custom" tag, exactly the bug fixed earlier in this session for
     * Label.
     */
    public function initializeDefaultTag(string $tag): void
    {
        parent::setTag($tag);
    }
}

/**
 * Unit tests for TraitTags.
 */
final class TraitTagsTest extends TestCase
{
    // --- getCustomTag() default state ---

    /**
     * 1) A freshly created element has no custom tag recorded.
     */
    public function testGetCustomTagIsNullByDefault(): void
    {
        $element = new ConcreteTaggedElement('myid');

        $this->assertNull($element->getCustomTag());
    }

    // --- setTag() ---

    /**
     * 2) Calling the public setTag() records the tag as "custom" and
     * applies it as the element's actual tag.
     */
    public function testSetTagRecordsCustomTagAndAppliesIt(): void
    {
        $element = new ConcreteTaggedElement('myid');

        $element->setTag('h2');

        $this->assertSame('h2', $element->getCustomTag());
        $this->assertSame('h2', $element->getTag());
    }

    /**
     * 3) setTag() returns $this, supporting fluent chaining.
     */
    public function testSetTagReturnsSelf(): void
    {
        $element = new ConcreteTaggedElement('myid');

        $this->assertSame($element, $element->setTag('span'));
    }

    /**
     * 4) A later call to setTag() overwrites the previously recorded
     * custom tag.
     */
    public function testSetTagOverwritesPreviousCustomTag(): void
    {
        $element = new ConcreteTaggedElement('myid');

        $element->setTag('h2');
        $element->setTag('h3');

        $this->assertSame('h3', $element->getCustomTag());
        $this->assertSame('h3', $element->getTag());
    }

    /**
     * 5) REGRESSION TEST for the sanitization fix: getCustomTag() must
     * reflect the same sanitized (lowercased, trimmed) value as getTag(),
     * not the raw input. Before the fix, $customTag stored the raw
     * argument while $tag (via parent::setTag()) stored the sanitized
     * one, so the two could diverge - confirmed standalone before writing
     * this assertion:
     *   setTag(" DIV ") => getTag() "div", getCustomTag() " DIV " (bug)
     */
    public function testGetCustomTagIsSanitizedLikeGetTag(): void
    {
        $element = new ConcreteTaggedElement('myid');

        $element->setTag(' DIV ');

        $this->assertSame('div', $element->getTag());
        $this->assertSame('div', $element->getCustomTag());
    }

    // --- parent::setTag() bypass (the mechanism behind the Label fix) ---

    /**
     * 6) Calling parent::setTag() directly (as a class's own constructor
     * does to set its default tag) applies the tag WITHOUT recording it
     * as a "custom" tag - this is the exact mechanism that fixed the
     * "input_global_label_tag config is never applied" bug in Label
     * earlier in this session.
     */
    public function testParentSetTagBypassesCustomTagRecording(): void
    {
        $element = new ConcreteTaggedElement('myid');

        $element->initializeDefaultTag('label');

        $this->assertSame('label', $element->getTag());
        $this->assertNull($element->getCustomTag());
    }

    /**
     * 7) After a parent::setTag() default initialization, a subsequent
     * genuine setTag() call from calling code is still correctly recorded
     * as custom - confirming the bypass only affects the internal
     * initialization call, not later legitimate customization.
     */
    public function testExplicitSetTagAfterDefaultInitializationIsRecorded(): void
    {
        $element = new ConcreteTaggedElement('myid');
        $element->initializeDefaultTag('label');

        $element->setTag('h2');

        $this->assertSame('h2', $element->getCustomTag());
        $this->assertSame('h2', $element->getTag());
    }
}
