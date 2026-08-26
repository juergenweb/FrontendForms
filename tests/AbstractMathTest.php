<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractMath;
use PHPUnit\Framework\TestCase;

/**
 * A minimal concrete AbstractMath subclass for testing. AbstractMath itself
 * is already fully concrete via its parent AbstractTextCaptcha (which
 * implements both createCaptchaImage() and createCaptchaInputField()), so
 * no method overrides are needed here to make the class instantiable -
 * only public wrappers for the protected methods/properties under test.
 */
final class ConcreteMath extends AbstractMath
{
    public function exposeCalculate(int $varOne, string $operator, int $varTwo): string
    {
        return $this->calculate($varOne, $operator, $varTwo);
    }

    public function exposeCreateRandomCalculation(): string
    {
        return $this->createRandomCalculation();
    }

    public function exposeResult(): string
    {
        return $this->result;
    }

    public function exposeGetCaptchaContent(): string
    {
        return $this->getCaptchaContent();
    }
}

/**
 * Unit tests for AbstractMath.
 */
final class AbstractMathTest extends TestCase
{
    // --- calculate() ---

    /**
     * 1) Addition is calculated correctly.
     */
    public function testCalculateAddition(): void
    {
        $captcha = new ConcreteMath();

        $this->assertSame('8', $captcha->exposeCalculate(3, '+', 5));
    }

    /**
     * 2) Subtraction is calculated correctly, including negative results.
     */
    public function testCalculateSubtraction(): void
    {
        $captcha = new ConcreteMath();

        $this->assertSame('6', $captcha->exposeCalculate(10, '-', 4));
        $this->assertSame('-2', $captcha->exposeCalculate(3, '-', 5));
    }

    /**
     * 3) Multiplication is calculated correctly.
     */
    public function testCalculateMultiplication(): void
    {
        $captcha = new ConcreteMath();

        $this->assertSame('12', $captcha->exposeCalculate(3, '*', 4));
    }

    /**
     * 4) An unrecognized operator falls back to "0" rather than throwing
     * or performing an unintended operation.
     */
    public function testCalculateWithUnknownOperatorReturnsZero(): void
    {
        $captcha = new ConcreteMath();

        $this->assertSame('0', $captcha->exposeCalculate(3, '?', 5));
        $this->assertSame('0', $captcha->exposeCalculate(3, '/', 5));
    }

    // --- createRandomCalculation() ---

    /**
     * 5) The returned calculation string has the format "digit(s)
     * operator digit(s)", using one of the three supported operators.
     */
    public function testCreateRandomCalculationReturnsValidExpressionFormat(): void
    {
        $captcha = new ConcreteMath();

        $calculation = $captcha->exposeCreateRandomCalculation();

        $this->assertMatchesRegularExpression('/^\d+[+\-*]\d+$/', $calculation);
    }

    /**
     * 6) Both operands are within the documented range of 1-9.
     */
    public function testCreateRandomCalculationOperandsAreWithinRange(): void
    {
        $captcha = new ConcreteMath();

        preg_match('/^(\d+)([+\-*])(\d+)$/', $captcha->exposeCreateRandomCalculation(), $matches);
        [, $num1, , $num2] = $matches;

        $this->assertGreaterThanOrEqual(1, (int) $num1);
        $this->assertLessThanOrEqual(9, (int) $num1);
        $this->assertGreaterThanOrEqual(1, (int) $num2);
        $this->assertLessThanOrEqual(9, (int) $num2);
    }

    /**
     * 7) The stored result matches what calculate() would produce for the
     * exact operands/operator that ended up in the returned expression
     * string - i.e. the two stay consistent with each other, whatever
     * random values were actually picked.
     */
    public function testCreateRandomCalculationStoresMatchingResult(): void
    {
        $captcha = new ConcreteMath();

        $calculation = $captcha->exposeCreateRandomCalculation();
        preg_match('/^(\d+)([+\-*])(\d+)$/', $calculation, $matches);
        [, $num1, $operator, $num2] = $matches;

        $expected = $captcha->exposeCalculate((int) $num1, $operator, (int) $num2);

        $this->assertSame($expected, $captcha->exposeResult());
    }

    // --- construction ---

    /**
     * 8) On construction, the captcha content is set to a valid
     * calculation expression string.
     */
    public function testConstructorSetsCaptchaContentToCalculation(): void
    {
        $captcha = new ConcreteMath();

        $this->assertMatchesRegularExpression('/^\d+[+\-*]\d+$/', $captcha->exposeGetCaptchaContent());
    }
}
