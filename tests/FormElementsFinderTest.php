<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Button;
use FrontendForms\FormElementsFinder;
use FrontendForms\InputCheckbox;
use FrontendForms\InputText;
use FrontendForms\PrivacyText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FormElementsFinder.
 *
 * The class holds a live PHP reference to the array passed into its
 * constructor, so every test builds a local $elements array and passes it
 * by reference - mutations made through the Finder (removeMultipleEntriesByClass())
 * must be visible on that same local array afterward.
 */
final class FormElementsFinderTest extends TestCase
{
    // --- reference behaviour ---

    /**
     * 1) The Finder operates on the SAME array the caller passed in (by
     * reference), not a disconnected copy - confirmed by adding an element
     * to the original array AFTER construction and checking the Finder
     * sees it.
     */
    public function testFinderSeesLaterChangesToTheReferencedArray(): void
    {
        $elements = [];
        $finder = new FormElementsFinder($elements);

        $elements['email'] = new InputText('email');

        $this->assertNotFalse($finder->getFormelementByName('email'));
    }

    // --- getFormFieldClasses() ---

    /**
     * 2) Returns the class name of every element in the array.
     */
    public function testGetFormFieldClassesReturnsClassNames(): void
    {
        $elements = [
            'email' => new InputText('email'),
            'submit' => new Button('submit'),
        ];
        $finder = new FormElementsFinder($elements);

        $this->assertSame(['InputText', 'Button'], $finder->getFormFieldClasses());
    }

    // --- formfieldExists() ---

    /**
     * 3) Checks against the elements' actual "name" attributes, matching
     * the documented behaviour.
     */
    public function testFormfieldExistsChecksAgainstNameAttribute(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertTrue($finder->formfieldExists('email'));
        $this->assertFalse($finder->formfieldExists('InputText'));
    }

    /**
     * 4) The check is case-insensitive and trims whitespace.
     */
    public function testFormfieldExistsIsCaseInsensitiveAndTrims(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertTrue($finder->formfieldExists('  EMAIL  '));
    }

    /**
     * 5) A name that doesn't belong to any element returns false.
     */
    public function testFormfieldExistsReturnsFalseWhenNotFound(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertFalse($finder->formfieldExists('not-there'));
    }

    // --- getFormelementByName() ---

    /**
     * 5) Finds an element by its name attribute.
     */
    public function testGetFormelementByNameFindsElement(): void
    {
        $email = new InputText('email');
        $elements = ['email' => $email];
        $finder = new FormElementsFinder($elements);

        $this->assertSame($email, $finder->getFormelementByName('email'));
    }

    /**
     * 6) Returns false when no element with that name exists.
     */
    public function testGetFormelementByNameReturnsFalseWhenNotFound(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertFalse($finder->getFormelementByName('not-there'));
    }

    // --- getFormElementsPosition() ---

    /**
     * 7) Returns the array key of the element matching the given object's
     * name attribute.
     */
    public function testGetFormElementsPositionReturnsMatchingKey(): void
    {
        $email = new InputText('email');
        $elements = [3 => new InputText('other'), 7 => $email];
        $finder = new FormElementsFinder($elements);

        $this->assertSame(7, $finder->getFormElementsPosition($email));
    }

    // --- getFormElementsByClass() ---

    /**
     * 8) Returns all elements whose exact (namespace-stripped) class name
     * matches - a plain string comparison, not instanceof, so subclasses
     * would NOT match (unlike formContainsElementByClass()).
     */
    public function testGetFormElementsByClassMatchesExactClassName(): void
    {
        $button = new Button('submit');
        $elements = [
            'email' => new InputText('email'),
            'submit' => $button,
        ];
        $finder = new FormElementsFinder($elements);

        $this->assertSame([$button], $finder->getFormElementsByClass('Button'));
    }

    /**
     * 9) A fully-namespaced class name is also accepted (the leading
     * namespace is stripped internally before comparing).
     */
    public function testGetFormElementsByClassAcceptsNamespacedClassName(): void
    {
        $button = new Button('submit');
        $elements = ['submit' => $button];
        $finder = new FormElementsFinder($elements);

        $this->assertSame([$button], $finder->getFormElementsByClass('FrontendForms\\Button'));
    }

    /**
     * 10) No matches returns an empty array.
     */
    public function testGetFormElementsByClassReturnsEmptyArrayWhenNoMatch(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertSame([], $finder->getFormElementsByClass('Button'));
    }

    // --- formContainsElementByClass() ---

    /**
     * 11) Counts how many elements are an instance of the given class.
     */
    public function testFormContainsElementByClassCountsMatches(): void
    {
        $elements = [
            'p1' => new PrivacyText('p1'),
            'p2' => new PrivacyText('p2'),
            'email' => new InputText('email'),
        ];
        $finder = new FormElementsFinder($elements);

        $this->assertSame(2, $finder->formContainsElementByClass('PrivacyText'));
    }

    /**
     * 12) Zero matches returns 0.
     */
    public function testFormContainsElementByClassReturnsZeroWhenNoMatch(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertSame(0, $finder->formContainsElementByClass('Button'));
    }

    // --- getElementsbyClass() ---

    /**
     * 13) With matches present, returns a single-element array wrapping
     * the (key-preserved) filtered elements - this unusual nested shape is
     * the existing, established API contract, relied on internally.
     */
    public function testGetElementsbyClassReturnsNestedStructureWhenFound(): void
    {
        $p1 = new PrivacyText('p1');
        $elements = [5 => $p1, 9 => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertSame([[5 => $p1]], $finder->getElementsbyClass('PrivacyText'));
    }

    /**
     * 14) With no matches, returns a genuinely empty array (not a
     * single-element array wrapping an empty array).
     */
    public function testGetElementsbyClassReturnsEmptyArrayWhenNoMatch(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertSame([], $finder->getElementsbyClass('PrivacyText'));
    }

    // --- removeMultipleEntriesByClass() (regression tests for the fix) ---

    /**
     * 15) REGRESSION TEST: with multiple matching elements, all but the
     * last (by array position) are removed from the referenced array, and
     * the key of the retained (last) element is returned.
     */
    public function testRemoveMultipleEntriesByClassKeepsOnlyLastMatch(): void
    {
        $p1 = new PrivacyText('p1');
        $p2 = new PrivacyText('p2');
        $elements = [3 => new InputText('other'), 5 => $p1, 12 => $p2, 20 => new InputText('other2')];
        $finder = new FormElementsFinder($elements);

        $result = $finder->removeMultipleEntriesByClass('PrivacyText');

        $this->assertSame(12, $result);
        $this->assertArrayNotHasKey(5, $elements);
        $this->assertArrayHasKey(12, $elements);
        $this->assertCount(3, $elements);
    }

    /**
     * 16) With exactly one matching element, nothing is removed and its
     * own key is returned.
     */
    public function testRemoveMultipleEntriesByClassLeavesSingleMatchUntouched(): void
    {
        $p1 = new PrivacyText('p1');
        $elements = [3 => new InputText('other'), 5 => $p1];
        $finder = new FormElementsFinder($elements);

        $result = $finder->removeMultipleEntriesByClass('PrivacyText');

        $this->assertSame(5, $result);
        $this->assertCount(2, $elements);
    }

    /**
     * 17) With no matching elements at all, null is returned and the array
     * is untouched.
     */
    public function testRemoveMultipleEntriesByClassReturnsNullWhenNoMatch(): void
    {
        $elements = ['email' => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $result = $finder->removeMultipleEntriesByClass('PrivacyText');

        $this->assertNull($result);
        $this->assertCount(1, $elements);
    }

    /**
     * 18) With three or more matches, only the very last is kept - all
     * earlier ones are removed.
     */
    public function testRemoveMultipleEntriesByClassWithThreeMatchesKeepsOnlyLast(): void
    {
        $elements = [
            1 => new PrivacyText('p1'),
            2 => new PrivacyText('p2'),
            3 => new PrivacyText('p3'),
        ];
        $finder = new FormElementsFinder($elements);

        $result = $finder->removeMultipleEntriesByClass('PrivacyText');

        $this->assertSame(3, $result);
        $this->assertSame([3], array_keys($elements));
    }

    // --- getElementPositionByName() ---

    /**
     * 19) Returns the array key of the element with the matching name
     * attribute.
     */
    public function testGetElementPositionByNameReturnsMatchingKey(): void
    {
        $elements = [3 => new InputText('other'), 7 => new InputText('email')];
        $finder = new FormElementsFinder($elements);

        $this->assertSame(7, $finder->getElementPositionByName('email'));
    }

    /**
     * 20) Returns 0 (the documented fallback) when no element with that
     * name exists.
     */
    public function testGetElementPositionByNameReturnsZeroWhenNotFound(): void
    {
        $elements = [3 => new InputText('other')];
        $finder = new FormElementsFinder($elements);

        $this->assertSame(0, $finder->getElementPositionByName('not-there'));
    }

    // --- getNamesOfInputFields() ---

    /**
     * 21) Returns the name attributes of only the Inputfields-family
     * elements, excluding non-input elements like Button.
     */
    public function testGetNamesOfInputFieldsExcludesNonInputElements(): void
    {
        $elements = [
            new InputText('email'),
            new InputCheckbox('agree'),
            new Button('submit'),
        ];
        $finder = new FormElementsFinder($elements);

        $this->assertSame(['email', 'agree'], $finder->getNamesOfInputFields());
    }

    /**
     * 22) An empty formElements array produces an empty result.
     */
    public function testGetNamesOfInputFieldsWithNoElementsReturnsEmptyArray(): void
    {
        $elements = [];
        $finder = new FormElementsFinder($elements);

        $this->assertSame([], $finder->getNamesOfInputFields());
    }
}