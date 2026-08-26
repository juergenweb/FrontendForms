<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractCaptcha;
use FrontendForms\Image;
use FrontendForms\Inputfields;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A minimal concrete AbstractCaptcha subclass for testing. Implements the
 * two abstract methods trivially (not under test here) and exposes the
 * protected methods under test via public wrappers, since PHPUnit test
 * classes aren't subclasses of AbstractCaptcha.
 */
final class ConcreteCaptcha extends AbstractCaptcha
{
    public function createCaptchaImage(string $formID): void
    {
    }

    public function createCaptchaInputField(string $formID): Inputfields
    {
        return new class ('captcha') extends Inputfields {
        };
    }

    public function exposeSetColor(string $color): array
    {
        return $this->setColor($color);
    }

    public function exposeSetCaptchaValidValue(string $content): self
    {
        return $this->setCaptchaValidValue($content);
    }

    public function exposeGetCaptchaValidValue(): string
    {
        return $this->getCaptchaValidValue();
    }

    public function exposeSetNumberOfColors(int $number): self
    {
        return $this->setNumberOfColors($number);
    }

    public function exposeGetNumberOfColors(): int
    {
        return $this->getNumberOfColors();
    }

    public function exposeGetWidth(): int
    {
        return $this->getWidth();
    }

    public function exposeGetHeight(): int
    {
        return $this->getHeight();
    }

    public function exposeGetNumberOfLines(): int
    {
        return $this->getNumberOfLines();
    }

    public function exposeGetLinesColor(): array
    {
        return $this->getLinesColor();
    }

    public function exposeCreateRGBColorArray(): array
    {
        return $this->createRGBColorArray();
    }

    public function exposeCreateReloadLink()
    {
        return $this->createReloadLink();
    }

    public function exposeCreateCaptchaImageTag(string $formID): Image
    {
        return $this->createCaptchaImageTag($formID);
    }
}

/**
 * Unit tests for AbstractCaptcha.
 *
 * getLinesColor()/getNumberOfLines()/getWidth()/getHeight()/getLinesType()
 * read from the "frontendforms" module config array, which depends on the
 * live test environment (see the Pico2InputRenderer/Bootstrap5InputRenderer
 * lessons from earlier in this session). Where a deterministic result is
 * needed, the relevant config keys are forced via ReflectionProperty rather
 * than relying on whatever the live environment happens to have configured.
 *
 * All expected outputs for hex2rgb() were confirmed by running the actual
 * algorithm standalone before writing the assertions.
 */
final class AbstractCaptchaTest extends TestCase
{
    private function captcha(): ConcreteCaptcha
    {
        return new ConcreteCaptcha();
    }

    private function setConfig(ConcreteCaptcha $captcha, array $values): void
    {
        $prop = new ReflectionProperty($captcha, 'frontendforms');
        $prop->setAccessible(true);
        $config = $prop->getValue($captcha);
        foreach ($values as $key => $value) {
            $config[$key] = $value;
        }
        $prop->setValue($captcha, $config);
    }

    // --- hex2rgb() ---

    /**
     * 1) A 6-digit hex color is converted to its RGB components.
     */
    public function testHex2rgbConvertsSixDigitHex(): void
    {
        $this->assertSame([255, 0, 0], AbstractCaptcha::hex2rgb('#ff0000'));
    }

    /**
     * 2) A 3-digit shorthand hex color is expanded and converted.
     */
    public function testHex2rgbConvertsThreeDigitShorthand(): void
    {
        $this->assertSame([255, 255, 255], AbstractCaptcha::hex2rgb('#fff'));
    }

    /**
     * 3) The leading "#" is optional.
     */
    public function testHex2rgbWorksWithoutLeadingHash(): void
    {
        $this->assertSame([0, 255, 0], AbstractCaptcha::hex2rgb('00ff00'));
    }

    /**
     * 4) A hex string of invalid length throws an InvalidArgumentException.
     */
    public function testHex2rgbThrowsForInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AbstractCaptcha::hex2rgb('#1234');
    }

    // --- linebreaksValuesToArray() ---

    /**
     * 5) A multi-line string is split into a trimmed array of lines.
     */
    public function testLinebreaksValuesToArraySplitsAndTrimsLines(): void
    {
        $this->assertSame(
            ['#ff0000', '#00ff00', '#0000ff'],
            AbstractCaptcha::linebreaksValuesToArray(" #ff0000 \n#00ff00\n #0000ff")
        );
    }

    /**
     * 6) An empty/null value falls back to the given (or default) fallback,
     * wrapped in a one-element array.
     */
    public function testLinebreaksValuesToArrayFallsBackWhenEmpty(): void
    {
        $this->assertSame(['#fff'], AbstractCaptcha::linebreaksValuesToArray(null));
        $this->assertSame(['#fff'], AbstractCaptcha::linebreaksValuesToArray(''));
        $this->assertSame(['#000'], AbstractCaptcha::linebreaksValuesToArray(null, '#000'));
    }

    // --- setCaptchaValidValue() / getCaptchaValidValue() ---

    /**
     * 7) The captcha's solution value can be set and read back.
     */
    public function testSetAndGetCaptchaValidValue(): void
    {
        $captcha = $this->captcha();
        $captcha->exposeSetCaptchaValidValue('42');

        $this->assertSame('42', $captcha->exposeGetCaptchaValidValue());
    }

    // --- setNumberOfColors() / getNumberOfColors() ---

    /**
     * 8) The number of distortion-line colors can be set and read back.
     */
    public function testSetAndGetNumberOfColors(): void
    {
        $captcha = $this->captcha();
        $captcha->exposeSetNumberOfColors(5);

        $this->assertSame(5, $captcha->exposeGetNumberOfColors());
    }

    // --- setColor() ---

    /**
     * 9) A hex color string is converted to its RGB array.
     */
    public function testSetColorConvertsHexString(): void
    {
        $captcha = $this->captcha();

        $this->assertSame([255, 0, 0], $captcha->exposeSetColor('#ff0000'));
    }

    /**
     * 10) A comma-separated RGB string is converted to an integer array.
     */
    public function testSetColorConvertsCommaSeparatedRgbString(): void
    {
        $captcha = $this->captcha();

        $this->assertSame([10, 20, 30], $captcha->exposeSetColor('10,20,30'));
    }

    /**
     * 10b) REGRESSION TEST for the fixed bug: an incomplete color string
     * (fewer than 3 comma-separated values, e.g. a misconfigured backend
     * setting) keeps all the values that ARE present, instead of being
     * incorrectly truncated down to just the first one.
     */
    public function testSetColorKeepsAllValuesWhenFewerThanThreeGiven(): void
    {
        $captcha = $this->captcha();

        $this->assertSame([10, 20], $captcha->exposeSetColor('10,20'));
    }

    /**
     * 10c) A color string with MORE than 3 comma-separated values is
     * correctly truncated to just the first 3 (R, G, B).
     */
    public function testSetColorTruncatesToThreeValuesWhenMoreThanThreeGiven(): void
    {
        $captcha = $this->captcha();

        $this->assertSame([10, 20, 30], $captcha->exposeSetColor('10,20,30,40,50'));
    }

    // --- config-dependent getters (forced via reflection) ---

    /**
     * 11) getWidth()/getHeight() cast the configured (string, as stored in
     * the database) dimensions to integers.
     */
    public function testGetWidthAndHeightCastConfigToInt(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_captchaWidth' => '150', 'input_captchaHeight' => '60']);

        $this->assertSame(150, $captcha->exposeGetWidth());
        $this->assertSame(60, $captcha->exposeGetHeight());
    }

    /**
     * 12) getNumberOfLines() casts the configured value to an integer.
     */
    public function testGetNumberOfLinesCastsConfigToInt(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_captchaNumberOfLines' => '4']);

        $this->assertSame(4, $captcha->exposeGetNumberOfLines());
    }

    /**
     * 13) getLinesColor() parses the configured newline-separated color
     * list into an array.
     */
    public function testGetLinesColorParsesConfiguredColorList(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_captchaLinesColor' => "#ff0000\n#00ff00"]);

        $this->assertSame(['#ff0000', '#00ff00'], $captcha->exposeGetLinesColor());
    }

    // --- createRGBColorArray() ---

    /**
     * 14) With custom colors configured (not "random"), createRGBColorArray()
     * cycles through the configured colors for as many lines as requested,
     * repeating them if there are more lines than colors.
     */
    public function testCreateRGBColorArrayCyclesThroughCustomColors(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, [
            'input_colorchooser' => 'custom',
            'input_captchaNumberOfLines' => '3',
            'input_captchaLinesColor' => "#ff0000\n#00ff00",
            'input_numberOfColorsOfLines' => 0,
        ]);

        $result = $captcha->exposeCreateRGBColorArray();

        $this->assertSame(
            [[255, 0, 0], [0, 255, 0], [255, 0, 0]],
            $result
        );
    }

    /**
     * 15) With zero configured lines, createRGBColorArray() returns an
     * empty array.
     */
    public function testCreateRGBColorArrayReturnsEmptyArrayWithZeroLines(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_captchaNumberOfLines' => '0']);

        $this->assertSame([], $captcha->exposeCreateRGBColorArray());
    }

    // --- createReloadLink() / createCaptchaImageTag() ---

    /**
     * 16) createReloadLink() returns the same Link instance on every call,
     * with its text/href/title configured.
     */
    public function testCreateReloadLinkReturnsConfiguredSameInstance(): void
    {
        $captcha = $this->captcha();

        $link = $captcha->exposeCreateReloadLink();

        $this->assertSame($link, $captcha->exposeCreateReloadLink());
        $this->assertSame('#', $link->getAttribute('href'));
    }

    /**
     * 17) createCaptchaImageTag() builds the image "src" attribute from the
     * form ID and the captcha's category/type.
     */
    public function testCreateCaptchaImageTagBuildsSrcFromFormId(): void
    {
        $captcha = $this->captcha();

        $image = $captcha->exposeCreateCaptchaImageTag('myform');

        $this->assertStringContainsString('formID=myform', $image->getAttribute('src'));
    }
}
