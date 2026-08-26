<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\BaseLogic;
use FrontendForms\Form;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Minimal concrete subclass, since BaseLogic is abstract, exposing its
 * protected methods via public wrappers.
 */
final class ConcreteBaseLogic extends BaseLogic
{
    public function exposeGetForm(): Form
    {
        return $this->getForm();
    }

    public function exposeCheckForParam(array $param): array
    {
        return $this->checkForParam($param);
    }

    public function exposeNormalizeStringArray(mixed $value): array
    {
        return $this->normalizeStringArray($value);
    }

    public function exposeResolveStringParam(array $params, string $label, int $index = 0): array
    {
        return $this->resolveStringParam($params, $label, $index);
    }
}

/**
 * Unit tests for BaseLogic.
 *
 * All expected outputs were confirmed by running the exact same
 * normalization/resolution logic standalone before writing the assertions.
 */
final class BaseLogicTest extends TestCase
{
    // --- setForm() / getForm() ---

    /**
     * 1) Calling getForm() before setForm() throws a clear exception.
     */
    public function testGetFormThrowsWhenCalledBeforeSetForm(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->exposeGetForm();
    }

    /**
     * 2) After setForm(), getForm() returns the same instance.
     */
    public function testGetFormReturnsAssignedForm(): void
    {
        $logic = new ConcreteBaseLogic();
        $form = new Form('myform');

        $logic->setForm($form);

        $this->assertSame($form, $logic->exposeGetForm());
    }

    // --- checkForParam() ---

    /**
     * 3) A non-empty array of non-empty values passes through unchanged.
     */
    public function testCheckForParamReturnsValidParam(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['a', 'b'], $logic->exposeCheckForParam(['a', 'b']));
    }

    /**
     * 4) An empty array throws.
     */
    public function testCheckForParamThrowsForEmptyArray(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->exposeCheckForParam([]);
    }

    /**
     * 5) An array containing an empty string value throws.
     */
    public function testCheckForParamThrowsForEmptyStringValue(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->exposeCheckForParam(['a', '']);
    }

    /**
     * 6) An array containing a null value throws.
     */
    public function testCheckForParamThrowsForNullValue(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->exposeCheckForParam(['a', null]);
    }

    // --- normalizeStringArray() ---

    /**
     * 7) A single string is wrapped in a one-element array.
     */
    public function testNormalizeStringArrayWithSingleString(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['jpg'], $logic->exposeNormalizeStringArray('jpg'));
    }

    /**
     * 8) An already-flat array of strings passes through with trimming.
     */
    public function testNormalizeStringArrayWithFlatArray(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['jpg', 'png'], $logic->exposeNormalizeStringArray([' jpg ', 'png']));
    }

    /**
     * 9) A nested single-element array is unwrapped one level.
     */
    public function testNormalizeStringArrayUnwrapsNestedArray(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['jpg', 'png'], $logic->exposeNormalizeStringArray([['jpg', 'png']]));
    }

    /**
     * 10) An integer input is converted to its string form.
     */
    public function testNormalizeStringArrayWithInteger(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['5'], $logic->exposeNormalizeStringArray(5));
    }

    /**
     * 11) Integer items inside an array are also converted to strings.
     */
    public function testNormalizeStringArrayWithIntegerItemsInArray(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['jpg', '5'], $logic->exposeNormalizeStringArray(['jpg', 5]));
    }

    /**
     * 12) Empty strings within an array are dropped, not kept as empty
     * entries.
     */
    public function testNormalizeStringArrayDropsEmptyEntries(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['jpg', 'png'], $logic->exposeNormalizeStringArray(['jpg', '', 'png']));
    }

    /**
     * 13) A bare float value resolves to an empty array (documented
     * limitation - float support is intentionally not implemented).
     */
    public function testNormalizeStringArrayWithBareFloatReturnsEmpty(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame([], $logic->exposeNormalizeStringArray(1.5));
    }

    /**
     * 14) A float value nested inside an array is silently skipped, while
     * valid string entries around it are kept.
     */
    public function testNormalizeStringArraySkipsFloatInsideArray(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['png'], $logic->exposeNormalizeStringArray([1.5, 'png']));
    }

    /**
     * 15) Any other unsupported type (e.g. bool) returns an empty array.
     */
    public function testNormalizeStringArrayWithUnsupportedTypeReturnsEmpty(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame([], $logic->exposeNormalizeStringArray(true));
    }

    // --- resolveStringParam() ---

    /**
     * 16) A valid string parameter at the default index resolves
     * correctly.
     */
    public function testResolveStringParamWithValidValue(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['jpg'], $logic->exposeResolveStringParam(['jpg'], 'extensions'));
    }

    /**
     * 17) A missing index throws, mentioning the given label.
     */
    public function testResolveStringParamThrowsForMissingIndex(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('extensions');

        $logic->exposeResolveStringParam([], 'extensions');
    }

    /**
     * 18) An explicit null value at the index throws.
     */
    public function testResolveStringParamThrowsForNullValue(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->exposeResolveStringParam([null], 'extensions');
    }

    /**
     * 19) A value that normalizes to an empty array (e.g. an empty
     * string) throws.
     */
    public function testResolveStringParamThrowsForValueNormalizingToEmpty(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->exposeResolveStringParam([''], 'extensions');
    }

    /**
     * 20) REGRESSION-STYLE TEST: a single "0" value is explicitly rejected
     * as a special case, distinct from the general empty-value check -
     * this is the exact mechanism whose absence (an empty array being
     * treated as a pass rather than a failure) caused the CAPTCHA
     * random_key bypass fixed earlier in Form.php; this class's own
     * zero/empty handling is confirmed correct here.
     */
    public function testResolveStringParamThrowsForSingleZero(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->expectException(InvalidArgumentException::class);

        $logic->exposeResolveStringParam(['0'], 'extensions');
    }

    /**
     * 21) A non-default index is correctly picked up.
     */
    public function testResolveStringParamWithNonDefaultIndex(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['b'], $logic->exposeResolveStringParam(['a', 'b'], 'extensions', 1));
    }

    /**
     * 22) An array value at the index is normalized the same way as a
     * scalar one.
     */
    public function testResolveStringParamWithArrayValue(): void
    {
        $logic = new ConcreteBaseLogic();

        $this->assertSame(['jpg', 'png'], $logic->exposeResolveStringParam([['jpg', 'png']], 'extensions'));
    }
}
