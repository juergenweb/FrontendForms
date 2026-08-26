<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractImageCaptcha;
use FrontendForms\InputRadioMultiple;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * A minimal concrete AbstractImageCaptcha subclass for testing. Exposes the
 * protected/private methods under test via public wrappers, since PHPUnit
 * test classes aren't subclasses of AbstractImageCaptcha.
 */
final class ConcreteImageCaptcha extends AbstractImageCaptcha
{
    public function exposeGetBlurlevel(): int
    {
        return $this->getBlurlevel();
    }

    public function exposeGetPixelatelevel(): int
    {
        return $this->getPixelatelevel();
    }

    public function exposeGetGrayscale(): int
    {
        return $this->getGrayscale();
    }

    public function exposeGetRandomImage(): string
    {
        return $this->getRandomImage();
    }

    public function exposeSetRandomImage(): void
    {
        $this->setRandomImage();
    }

    public function setImagePath(string $path): void
    {
        $prop = new ReflectionProperty($this, 'imagePath');
        $prop->setAccessible(true);
        $prop->setValue($this, $path);
    }

    public function exposeResizeImage(string $file, int $w, int $h, bool $crop = false): \GdImage
    {
        return $this->resizeImage($file, $w, $h, $crop);
    }
}

/**
 * Unit tests for AbstractImageCaptcha.
 *
 * setRandomImage(), resizeImage(), createOptions(), createCaptchaImage(),
 * and createCaptchaInputField() are NOT covered here - they need real image
 * files on disk (or, for createCaptchaInputField(), a fully assembled
 * Inputfields tree), which is better suited to an integration test (same
 * reasoning as other filesystem/live-data-dependent methods skipped
 * elsewhere in this session).
 *
 * getBlurlevel()/getPixelatelevel()/getGrayscale() read from the
 * "frontendforms" module config array, which depends on the live test
 * environment - forced to known values via ReflectionProperty for
 * deterministic results (same technique as AbstractCaptchaTest). Config
 * values are deliberately set as STRINGS (not ints), matching how
 * ProcessWire actually stores/returns module config values - this is what
 * originally caught the missing (int) cast in the "value within range"
 * branch of getBlurlevel()/getPixelatelevel(), which would otherwise throw
 * a TypeError under strict_types when returning a string from a method
 * declared ": int".
 */
final class AbstractImageCaptchaTest extends TestCase
{
    private function captcha(): ConcreteImageCaptcha
    {
        return new ConcreteImageCaptcha();
    }

    private function setConfig(ConcreteImageCaptcha $captcha, array $values): void
    {
        $prop = new ReflectionProperty($captcha, 'frontendforms');
        $prop->setAccessible(true);
        $config = $prop->getValue($captcha);
        foreach ($values as $key => $value) {
            $config[$key] = $value;
        }
        $prop->setValue($captcha, $config);
    }

    /**
     * getImageCategory() is private (not protected), so it can't be reached
     * via a public wrapper method on a subclass the way the protected
     * methods elsewhere in this file are - calling it from a subclass
     * method body triggers Wire::__call() instead of a normal inherited
     * call, which hung rather than failing cleanly. ReflectionMethod is the
     * correct tool for invoking a private method directly in a test.
     */
    private function callGetImageCategory(ConcreteImageCaptcha $captcha, string $path): string
    {
        $method = new ReflectionMethod($captcha, 'getImageCategory');
        $method->setAccessible(true);
        return $method->invoke($captcha, $path);
    }

    // --- construction ---

    /**
     * 1) The category is set to "image" on construction.
     */
    public function testConstructorSetsImageCategory(): void
    {
        $captcha = $this->captcha();

        $reflection = new ReflectionProperty($captcha, 'category');
        $reflection->setAccessible(true);
        $this->assertSame('image', $reflection->getValue($captcha));
    }

    /**
     * 2) The underlying selection field is a properly constructed
     * InputRadioMultiple with a description already set.
     */
    public function testConstructorCreatesCaptchaInputWithDescription(): void
    {
        $captcha = $this->captcha();

        $reflection = new ReflectionProperty($captcha, 'captchaInput');
        $reflection->setAccessible(true);
        $captchaInput = $reflection->getValue($captcha);

        $this->assertInstanceOf(InputRadioMultiple::class, $captchaInput);
        $this->assertNotSame('', $captchaInput->getDescription()->getText());
    }

    // --- getBlurlevel() ---

    /**
     * 3) A configured value within the valid range (0-10) is returned as-is.
     */
    public function testGetBlurlevelReturnsValueWithinRange(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_blurlevel' => '5']);

        $this->assertSame(5, $captcha->exposeGetBlurlevel());
    }

    /**
     * 4) A negative configured value is clamped to 0.
     */
    public function testGetBlurlevelClampsNegativeValueToZero(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_blurlevel' => '-3']);

        $this->assertSame(0, $captcha->exposeGetBlurlevel());
    }

    /**
     * 5) A configured value above 10 is clamped to 10.
     */
    public function testGetBlurlevelClampsValueAboveTenToTen(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_blurlevel' => '25']);

        $this->assertSame(10, $captcha->exposeGetBlurlevel());
    }

    /**
     * 6) The boundary values 0 and 10 themselves are returned unclamped.
     */
    public function testGetBlurlevelBoundaryValuesAreNotClamped(): void
    {
        $captcha = $this->captcha();

        $this->setConfig($captcha, ['input_blurlevel' => '0']);
        $this->assertSame(0, $captcha->exposeGetBlurlevel());

        $this->setConfig($captcha, ['input_blurlevel' => '10']);
        $this->assertSame(10, $captcha->exposeGetBlurlevel());
    }

    // --- getPixelatelevel() ---

    /**
     * 7) A configured value within the valid range (0-5) is returned as-is.
     */
    public function testGetPixelatelevelReturnsValueWithinRange(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_pixelatelevel' => '3']);

        $this->assertSame(3, $captcha->exposeGetPixelatelevel());
    }

    /**
     * 8) A negative configured value is clamped to 0.
     */
    public function testGetPixelatelevelClampsNegativeValueToZero(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_pixelatelevel' => '-1']);

        $this->assertSame(0, $captcha->exposeGetPixelatelevel());
    }

    /**
     * 9) A configured value above 5 is clamped to 5 - note the DIFFERENT
     * upper bound compared to getBlurlevel() (5, not 10).
     */
    public function testGetPixelatelevelClampsValueAboveFiveToFive(): void
    {
        $captcha = $this->captcha();
        $this->setConfig($captcha, ['input_pixelatelevel' => '8']);

        $this->assertSame(5, $captcha->exposeGetPixelatelevel());
    }

    // --- getGrayscale() ---

    /**
     * 10) The configured value is cast to an integer (module config values
     * are stored as strings in the database).
     */
    public function testGetGrayscaleCastsConfigValueToInt(): void
    {
        $captcha = $this->captcha();

        $this->setConfig($captcha, ['input_grayscale' => '1']);
        $this->assertSame(1, $captcha->exposeGetGrayscale());

        $this->setConfig($captcha, ['input_grayscale' => '']);
        $this->assertSame(0, $captcha->exposeGetGrayscale());
    }

    // --- getRandomImage() ---

    /**
     * 11) A freshly created captcha has no random image path set yet
     * (setRandomImage() was never called).
     */
    public function testGetRandomImageIsEmptyByDefault(): void
    {
        $this->assertSame('', $this->captcha()->exposeGetRandomImage());
    }

    // --- getImageCategory() ---

    /**
     * 12) The category is derived from the name of the image's parent
     * directory.
     */
    public function testGetImageCategoryExtractsParentDirectoryName(): void
    {
        $captcha = $this->captcha();

        $this->assertSame(
            'car',
            $this->callGetImageCategory($captcha, '/path/to/captcha-images/car/image1.jpg')
        );
    }

    /**
     * 13) Different images in different category folders yield different
     * categories.
     */
    public function testGetImageCategoryVariesByFolder(): void
    {
        $captcha = $this->captcha();

        $this->assertSame('house', $this->callGetImageCategory($captcha, '/images/house/photo3.jpg'));
        $this->assertSame('tree', $this->callGetImageCategory($captcha, '/images/tree/photo7.jpg'));
    }

    /**
     * 14) REGRESSION TEST for the fixed bug: an empty (or non-existent)
     * captcha-images directory used to cause array_rand() to throw an
     * unhelpful ValueError. Now it throws a clear, descriptive
     * RuntimeException naming the path that was searched.
     */
    public function testSetRandomImageThrowsClearErrorWhenNoImagesFound(): void
    {
        $captcha = $this->captcha();
        $captcha->setImagePath(sys_get_temp_dir() . '/frontendforms-empty-captcha-dir-' . uniqid() . '/');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/No captcha images found/');

        $captcha->exposeSetRandomImage();
    }

    /**
     * 15) REGRESSION TEST for the fixed bug: a missing/unreadable image
     * file used to cause getimagesize() to return false, which was then
     * destructured via list(), producing null width/height and a
     * division-by-zero when computing the aspect ratio. Now it throws a
     * clear, descriptive RuntimeException naming the file instead.
     */
    public function testResizeImageThrowsClearErrorForUnreadableFile(): void
    {
        $captcha = $this->captcha();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/could not be read/');

        $captcha->exposeResizeImage('/nonexistent/path/to/image-' . uniqid() . '.jpg', 100, 100);
    }
}
