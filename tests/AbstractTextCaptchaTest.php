<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractTextCaptcha;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A minimal concrete AbstractTextCaptcha subclass for testing. Exposes the
 * protected methods under test via public wrappers, since PHPUnit test
 * classes aren't subclasses of AbstractTextCaptcha.
 */
final class ConcreteTextCaptcha extends AbstractTextCaptcha
{
    public function exposeGetBackgroundType(): string
    {
        return $this->getBackgroundType();
    }

    public function exposeGetBackgroundColor(): array
    {
        return $this->getBackgroundColor();
    }

    public function exposeSetNumberOfBackgroundColors(int $number): self
    {
        return $this->setNumberOfBackgroundColors($number);
    }

    public function exposeGetNumberOfBackgroundColors(): int
    {
        return $this->getNumberOfBackgroundColors();
    }

    public function exposeGetTextColor(): array
    {
        return $this->getTextColor();
    }

    public function exposeGetFontSize(): int
    {
        return $this->getFontSize();
    }

    public function exposeSetCaptchaContent(string $content): self
    {
        return $this->setCaptchaContent($content);
    }

    public function exposeGetCaptchaContent(): string
    {
        return $this->getCaptchaContent();
    }

    public function exposeCreateRandomColorsArray(int $number): array
    {
        return $this->createRandomColorsArray($number);
    }
}

/**
 * Unit tests for AbstractTextCaptcha.
 *
 * getFontFamily(), generateTextPosition(), createText(), createBackground(),
 * and createCaptchaImage() are NOT covered here - they need real TTF font
 * files on disk and/or GD image resources, which is better suited to an
 * integration test (same reasoning as other filesystem/GD-dependent methods
 * skipped elsewhere in this session).
 *
 * getBackgroundType()/getBackgroundColor()/getNumberOfBackgroundColors()/getTextColor()/
 * getFontSize() read from the "frontendforms" module config array, which
 * depends on the live test environment - forced to known values via
 * ReflectionProperty for deterministic results (same technique as
 * AbstractCaptchaTest/AbstractImageCaptchaTest/AbstractCharsetTest).
 * Numeric config values are set as STRINGS, matching how ProcessWire
 * actually stores/returns module config values.
 *
 * Note on createRandomColorsArray(): the fixed bug (a duplicate-variable
 * nested loop) never actually produced wrong output - the outer loop was
 * simply dead code that ran once harmlessly. testCreateRandomColorsArray*()
 * below verify correct behaviour but would have passed against the buggy
 * version too; the fix was about removing misleading dead code, not
 * correctness.
 */
final class AbstractTextCaptchaTest extends TestCase
{
    private function setConfig(ConcreteTextCaptcha $captcha, array $values): void
    {
        $prop = new ReflectionProperty($captcha, 'frontendforms');
        $prop->setAccessible(true);
        $config = $prop->getValue($captcha);
        foreach ($values as $key => $value) {
            $config[$key] = $value;
        }
        $prop->setValue($captcha, $config);
    }

    // --- construction ---

    /**
     * 1) The category is set to "text" on construction.
     */
    public function testConstructorSetsTextCategory(): void
    {
        $captcha = new ConcreteTextCaptcha();

        $reflection = new ReflectionProperty($captcha, 'category');
        $reflection->setAccessible(true);
        $this->assertSame('text', $reflection->getValue($captcha));
    }

    // --- getBackgroundType() ---

    /**
     * 2) A configured "random" or "custom" value is returned as-is.
     */
    public function testGetBackgroundTypeReturnsConfiguredValue(): void
    {
        $captcha = new ConcreteTextCaptcha();
        $this->setConfig($captcha, ['input_bgcolorchooser' => 'custom']);

        $this->assertSame('custom', $captcha->exposeGetBackgroundType());
    }

    // --- getBackgroundColor() ---

    /**
     * 3) The configured newline-separated background color list is parsed
     * into an array.
     */
    public function testGetBackgroundColorParsesConfiguredColorList(): void
    {
        $captcha = new ConcreteTextCaptcha();
        $this->setConfig($captcha, ['input_bgCustomColors' => "#ffffff\n#eeeeee"]);

        $this->assertSame(['#ffffff', '#eeeeee'], $captcha->exposeGetBackgroundColor());
    }

    // --- setNumberOfBackgroundColors() / getNumberOfBackgroundColors() ---

    /**
     * 4) The number of background colors can be set and read back.
     */
    public function testSetAndGetNumberOfBackgroundColors(): void
    {
        $captcha = new ConcreteTextCaptcha();
        $captcha->exposeSetNumberOfBackgroundColors(4);

        $this->assertSame(4, $captcha->exposeGetNumberOfBackgroundColors());
    }

    /**
     * 5) REGRESSION TEST for the missing (int) cast: module config values
     * are stored as strings in the database, so the configured value is set
     * here as a STRING ("3"), not an int. Before the fix, returning that
     * string from a method declared ": int" under declare(strict_types=1)
     * would have thrown a TypeError.
     */
    public function testGetNumberOfBackgroundColorsCastsConfigToInt(): void
    {
        $captcha = new ConcreteTextCaptcha();
        $this->setConfig($captcha, ['input_bgnumberOfColors' => '3']);

        $this->assertSame(3, $captcha->exposeGetNumberOfBackgroundColors());
    }

    // --- getTextColor() ---

    /**
     * 6) The configured text color hex string is converted to an RGB array.
     */
    public function testGetTextColorConvertsConfiguredHex(): void
    {
        $captcha = new ConcreteTextCaptcha();
        $this->setConfig($captcha, ['input_captchaTextColor' => '#ff0000']);

        $this->assertSame([255, 0, 0], $captcha->exposeGetTextColor());
    }

    // --- getFontSize() ---

    /**
     * 7) The configured font size is cast to an integer.
     */
    public function testGetFontSizeCastsConfigToInt(): void
    {
        $captcha = new ConcreteTextCaptcha();
        $this->setConfig($captcha, ['input_captchaFontsize' => '18']);

        $this->assertSame(18, $captcha->exposeGetFontSize());
    }

    // --- setCaptchaContent() / getCaptchaContent() ---

    /**
     * 8) The captcha's content string can be set and read back.
     */
    public function testSetAndGetCaptchaContent(): void
    {
        $captcha = new ConcreteTextCaptcha();
        $captcha->exposeSetCaptchaContent('AB3D');

        $this->assertSame('AB3D', $captcha->exposeGetCaptchaContent());
    }

    // --- createRandomColorsArray() ---

    /**
     * 9) The returned array has exactly as many entries as requested.
     */
    public function testCreateRandomColorsArrayReturnsRequestedCount(): void
    {
        $captcha = new ConcreteTextCaptcha();

        $this->assertCount(5, $captcha->exposeCreateRandomColorsArray(5));
    }

    /**
     * 10) Each entry is a valid [r, g, b] triplet with components in the
     * range 0-255.
     */
    public function testCreateRandomColorsArrayValuesAreValidRgbComponents(): void
    {
        $captcha = new ConcreteTextCaptcha();

        foreach ($captcha->exposeCreateRandomColorsArray(10) as $color) {
            $this->assertCount(3, $color);
            foreach ($color as $component) {
                $this->assertGreaterThanOrEqual(0, $component);
                $this->assertLessThanOrEqual(255, $component);
            }
        }
    }

    /**
     * 11) With zero requested colors, an empty array is returned.
     */
    public function testCreateRandomColorsArrayWithZeroReturnsEmptyArray(): void
    {
        $captcha = new ConcreteTextCaptcha();

        $this->assertSame([], $captcha->exposeCreateRandomColorsArray(0));
    }

    // --- createCaptchaInputField() ---

    /**
     * 12) The generated input field's "name" attribute combines the given
     * form ID with "-captcha".
     */
    public function testCreateCaptchaInputFieldSetsNameFromFormId(): void
    {
        $captcha = new ConcreteTextCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame('myform-captcha', $field->getAttribute('name'));
    }

    /**
     * 13) Both the field's own wrapper and its input wrapper get the
     * "captcha" CSS class (checked with assertContains(), since the wrapper
     * may already carry a framework-specific default class from the live
     * test environment).
     */
    public function testCreateCaptchaInputFieldAddsCaptchaCssClassToWrappers(): void
    {
        $captcha = new ConcreteTextCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertContains('captcha', $field->getFieldWrapper()->getAttribute('class'));
        $this->assertContains('captcha', $field->getInputWrapper()->getAttribute('class'));
    }
}
