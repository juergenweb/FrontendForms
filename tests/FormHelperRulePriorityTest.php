<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FormHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FormHelper::sortRulesByPriority() and the now-deprecated
 * FormHelper::putRequiredOnTop().
 *
 * Both are pure, stateless static methods with no wire()/ProcessWire
 * dependency (per FormHelper's own class docblock), so they're tested
 * directly here without any of the reflection-based instantiation
 * workarounds needed elsewhere in this suite.
 *
 * Background: Form::isValid() registers each field's validation rules
 * with the Valitron validator in array order, and Valitron runs rules in
 * that same registration order. sortRulesByPriority() was introduced so
 * that specific rules can be guaranteed to run before others regardless
 * of the order setRule() was originally called in - initially to ensure
 * "required"/"fileRequired" always run first (previously handled by the
 * now-superseded putRequiredOnTop()) and to make allowedMimeTypes/
 * forbiddenMimeTypes always run before allowedFileExt/forbiddenFileExt
 * (a content-based check should reject a spoofed file before a
 * weaker, filename-based check would even run). Priorities are looked
 * up from the public FormHelper::RULE_PRIORITIES map, so adding a new
 * ordering guarantee in the future only requires adding an entry there.
 */
final class FormHelperRulePriorityTest extends TestCase
{
    // --- sortRulesByPriority() ---

    /**
     * 1) allowedMimeTypes is sorted ahead of allowedFileExt, even though
     * it appears after it in the original array - the exact scenario
     * this method was introduced for.
     */
    public function testMimeTypeRuleSortedBeforeFileExtRule(): void
    {
        $rules = [
            'allowedFileExt' => ['options' => ['jpg', 'png']],
            'allowedMimeTypes' => ['options' => ['image/jpeg']],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(['allowedMimeTypes', 'allowedFileExt'], array_keys($sorted));
    }

    /**
     * 2) Same guarantee for the "forbidden" variants of the same rules.
     */
    public function testForbiddenMimeTypeRuleSortedBeforeForbiddenFileExtRule(): void
    {
        $rules = [
            'forbiddenFileExt' => ['options' => ['exe']],
            'forbiddenMimeTypes' => ['options' => ['application/x-msdownload']],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(['forbiddenMimeTypes', 'forbiddenFileExt'], array_keys($sorted));
    }

    /**
     * 3) With all four MIME/extension rules mixed together with an
     * unrelated, unlisted rule, both MIME-type rules end up ahead of
     * both extension rules, and the unlisted rule (default priority
     * 100) ends up last - after every explicitly prioritized rule,
     * regardless of where it originally appeared in the array.
     */
    public function testAllFourRulesMixedWithUnlistedRule(): void
    {
        $rules = [
            'forbiddenFileExt' => ['options' => []],
            'someOtherRule' => ['options' => []],
            'forbiddenMimeTypes' => ['options' => []],
            'allowedFileExt' => ['options' => []],
            'allowedMimeTypes' => ['options' => []],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(
            ['forbiddenMimeTypes', 'allowedMimeTypes', 'forbiddenFileExt', 'allowedFileExt', 'someOtherRule'],
            array_keys($sorted)
        );
    }

    /**
     * 4) Rules with no entry in RULE_PRIORITIES (default priority 100)
     * keep their original relative order among each other - the sort is
     * stable, not alphabetical or otherwise reshuffled.
     */
    public function testUnlistedRulesKeepOriginalRelativeOrder(): void
    {
        $rules = [
            'zebra' => ['options' => []],
            'apple' => ['options' => []],
            'mango' => ['options' => []],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(['zebra', 'apple', 'mango'], array_keys($sorted));
    }

    /**
     * 5) "required" is sorted to the front, ahead of MIME/extension
     * rules (and everything else), since it has the lowest priority (0)
     * in RULE_PRIORITIES.
     */
    public function testRequiredSortedFirst(): void
    {
        $rules = [
            'allowedFileExt' => ['options' => []],
            'allowedMimeTypes' => ['options' => []],
            'required' => ['options' => []],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(['required', 'allowedMimeTypes', 'allowedFileExt'], array_keys($sorted));
    }

    /**
     * 6) "fileRequired" is likewise sorted to the front when present
     * without "required".
     */
    public function testFileRequiredSortedFirst(): void
    {
        $rules = [
            'allowedFileExt' => ['options' => []],
            'fileRequired' => ['options' => []],
            'noEmptyFiles' => ['options' => []],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(['fileRequired', 'allowedFileExt', 'noEmptyFiles'], array_keys($sorted));
    }

    /**
     * 7) REGRESSION TEST / edge case that motivated retiring
     * putRequiredOnTop(): a file upload field with the "required" rule
     * gets "fileRequired" added alongside it (see Form::isValid()), so
     * both can be present on the same field at once. Both must end up
     * at the front (in their original relative order, since they're
     * tied at priority 0), not just one of them.
     *
     * putRequiredOnTop() only ever moved "fileRequired" to the front in
     * this situation, leaving "required" wherever it originally was -
     * see testPutRequiredOnTopOnlyMovesFileRequiredWhenBothArePresent()
     * below for a characterization test of that specific limitation.
     */
    public function testBothRequiredAndFileRequiredSortedToTheFrontTogether(): void
    {
        $rules = [
            'allowedMimeTypes' => ['options' => []],
            'required' => ['options' => []],
            'allowedFileExt' => ['options' => []],
            'fileRequired' => ['options' => []],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(
            ['required', 'fileRequired', 'allowedMimeTypes', 'allowedFileExt'],
            array_keys($sorted)
        );
    }

    /**
     * 8) Without any required-type rule present, MIME-type-before-
     * extension ordering is still applied, and all other rules are
     * otherwise left in their original relative order.
     */
    public function testMimeBeforeExtensionOrderingAppliesWithoutRequiredRule(): void
    {
        $rules = [
            'allowedFileExt' => ['options' => []],
            'allowedMimeTypes' => ['options' => []],
            'noEmptyFiles' => ['options' => []],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(['allowedMimeTypes', 'allowedFileExt', 'noEmptyFiles'], array_keys($sorted));
    }

    /**
     * 9) An empty rules array is returned unchanged.
     */
    public function testEmptyArrayReturnsEmptyArray(): void
    {
        $this->assertSame([], FormHelper::sortRulesByPriority([]));
    }

    /**
     * 10) A single-rule array is returned unchanged (nothing to sort).
     */
    public function testSingleRuleArrayIsUnchanged(): void
    {
        $rules = ['onlyRule' => ['options' => ['foo']]];

        $this->assertSame($rules, FormHelper::sortRulesByPriority($rules));
    }

    /**
     * 11) Sorting reorders the array's keys, but each rule's own value
     * (its options/customMsg/etc. sub-array) travels with it correctly -
     * this isn't just a key-order shuffle that loses track of which
     * value belongs to which rule.
     */
    public function testRuleValuesStayCorrectlyAssociatedWithTheirKeysAfterSorting(): void
    {
        $rules = [
            'allowedFileExt' => ['options' => ['jpg', 'png'], 'customMsg' => 'bad extension'],
            'allowedMimeTypes' => ['options' => ['image/jpeg'], 'customMsg' => 'bad mime'],
        ];

        $sorted = FormHelper::sortRulesByPriority($rules);

        $this->assertSame(['image/jpeg'], $sorted['allowedMimeTypes']['options']);
        $this->assertSame('bad mime', $sorted['allowedMimeTypes']['customMsg']);
        $this->assertSame(['jpg', 'png'], $sorted['allowedFileExt']['options']);
        $this->assertSame('bad extension', $sorted['allowedFileExt']['customMsg']);
    }

    // --- putRequiredOnTop() (deprecated, kept for backward compatibility) ---

    /**
     * 12) Still works for its original, simple purpose: "required" alone
     * is moved to the front.
     */
    public function testDeprecatedPutRequiredOnTopStillMovesRequiredToFront(): void
    {
        $rules = [
            'allowedFileExt' => ['options' => []],
            'required' => ['options' => []],
        ];

        $result = FormHelper::putRequiredOnTop($rules);

        $this->assertSame(['required', 'allowedFileExt'], array_keys($result));
    }

    /**
     * 13) CHARACTERIZATION TEST documenting the method's known
     * limitation (see its @deprecated docblock): when both "required"
     * and "fileRequired" are present, only "fileRequired" is moved to
     * the front - "required" is left wherever it originally was. This
     * is exactly the gap sortRulesByPriority() closes (see test 7
     * above). Kept as a test so this documented limitation doesn't
     * silently change if the deprecated method is ever edited.
     */
    public function testDeprecatedPutRequiredOnTopOnlyMovesFileRequiredWhenBothArePresent(): void
    {
        $rules = [
            'allowedMimeTypes' => ['options' => []],
            'required' => ['options' => []],
            'allowedFileExt' => ['options' => []],
            'fileRequired' => ['options' => []],
        ];

        $result = FormHelper::putRequiredOnTop($rules);

        $this->assertSame(
            ['fileRequired', 'allowedMimeTypes', 'required', 'allowedFileExt'],
            array_keys($result)
        );
    }

    /**
     * 14) With neither "required" nor "fileRequired" present, the array
     * is returned unchanged.
     */
    public function testDeprecatedPutRequiredOnTopLeavesArrayUnchangedWithoutRequiredRule(): void
    {
        $rules = [
            'allowedFileExt' => ['options' => []],
            'noEmptyFiles' => ['options' => []],
        ];

        $this->assertSame($rules, FormHelper::putRequiredOnTop($rules));
    }
}
