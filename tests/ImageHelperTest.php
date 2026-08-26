<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\ImageHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ImageHelper.
 *
 * detectImage()/analyzeImage() are exercised against the real fixture
 * files already used elsewhere in this test suite, with their known,
 * verified pixel dimensions (landscape.jpg = 90x60, sample.png = 40x30).
 */
final class ImageHelperTest extends TestCase
{
    private function fixture(string $name): string
    {
        return __DIR__ . '/fixtures/' . $name;
    }

    // --- detectImage() ---

    /**
     * 1) A genuine JPEG file is correctly detected, returning a real
     * getimagesize()-style array.
     */
    public function testDetectImageAcceptsRealJpeg(): void
    {
        $helper = new ImageHelper();
        $path = $this->fixture('landscape.jpg');

        $result = $helper->detectImage(filesize($path), 'image/jpeg', null, $path);

        $this->assertIsArray($result);
        $this->assertSame(90, $result[0]);
        $this->assertSame(60, $result[1]);
    }

    /**
     * 2) A size of 0 (or less) is rejected.
     */
    public function testDetectImageRejectsZeroSize(): void
    {
        $helper = new ImageHelper();

        $this->assertFalse($helper->detectImage(0, 'image/jpeg', null, $this->fixture('landscape.jpg')));
    }

    /**
     * 3) A size exceeding the max analyzable size is rejected.
     */
    public function testDetectImageRejectsOversizedFile(): void
    {
        $helper = new ImageHelper();

        $tooLarge = ImageHelper::MAX_IMAGE_ANALYZE_SIZE + 1;

        $this->assertFalse($helper->detectImage($tooLarge, 'image/jpeg', null, $this->fixture('landscape.jpg')));
    }

    /**
     * 4) A null or non-image MIME type is rejected.
     */
    public function testDetectImageRejectsNonImageMimeType(): void
    {
        $helper = new ImageHelper();
        $path = $this->fixture('landscape.jpg');

        $this->assertFalse($helper->detectImage(filesize($path), null, null, $path));
        $this->assertFalse($helper->detectImage(filesize($path), 'application/pdf', null, $path));
    }

    /**
     * 5) SVG is accepted structurally (empty array), since
     * getimagesize() cannot read SVG content.
     */
    public function testDetectImageReturnsEmptyArrayForSvg(): void
    {
        $helper = new ImageHelper();

        $result = $helper->detectImage(100, 'image/svg+xml', null, '');

        $this->assertSame([], $result);
    }

    /**
     * 6) Genuinely invalid image content (correct MIME claim, but not
     * real image bytes) is rejected.
     */
    public function testDetectImageRejectsCorruptContent(): void
    {
        $helper = new ImageHelper();

        $result = $helper->detectImage(20, 'image/jpeg', 'this is not real jpeg data!!', '');

        $this->assertFalse($result);
    }

    /**
     * 7) Raw content can be analyzed directly, without a file path.
     */
    public function testDetectImageWorksFromRawContent(): void
    {
        $helper = new ImageHelper();
        $content = file_get_contents($this->fixture('sample.png'));

        $result = $helper->detectImage(strlen($content), 'image/png', $content);

        $this->assertIsArray($result);
        $this->assertSame(40, $result[0]);
        $this->assertSame(30, $result[1]);
    }

    // --- analyzeImage() ---

    /**
     * 8) A real JPEG's dimensions and default orientation (1, since the
     * fixture has no EXIF data) are correctly returned.
     */
    public function testAnalyzeImageReturnsDimensionsAndOrientation(): void
    {
        $helper = new ImageHelper();
        $path = $this->fixture('landscape.jpg');
        $img = $helper->detectImage(filesize($path), 'image/jpeg', null, $path);

        $result = $helper->analyzeImage($path, 'image/jpeg', filesize($path), $img);

        $this->assertSame(['width' => 90, 'height' => 60, 'orientation' => 1], $result);
    }

    /**
     * 9) A null MIME type returns null.
     */
    public function testAnalyzeImageReturnsNullForNullMime(): void
    {
        $helper = new ImageHelper();

        $this->assertNull($helper->analyzeImage('', null, 100, []));
    }

    /**
     * 10) SVG returns zero dimensions with default orientation.
     */
    public function testAnalyzeImageReturnsZeroDimensionsForSvg(): void
    {
        $helper = new ImageHelper();

        $result = $helper->analyzeImage('', 'image/svg+xml', 100, []);

        $this->assertSame(['width' => 0, 'height' => 0, 'orientation' => 1], $result);
    }

    /**
     * 11) An empty $img array (from a failed detectImage() call) returns
     * null for a raster image type.
     */
    public function testAnalyzeImageReturnsNullForEmptyImgArray(): void
    {
        $helper = new ImageHelper();

        $this->assertNull($helper->analyzeImage('', 'image/jpeg', 100, []));
    }

    // --- getImageOrientation() ---

    /**
     * 12) A fixture with no EXIF data returns the default orientation 1.
     */
    public function testGetImageOrientationReturnsDefaultForNoExifData(): void
    {
        $helper = new ImageHelper();
        $path = $this->fixture('landscape.jpg');

        $this->assertSame(1, $helper->getImageOrientation($path, 'image/jpeg', filesize($path)));
    }

    /**
     * 13) A MIME type that doesn't support EXIF (e.g. PNG) always returns
     * the default orientation, without attempting to parse EXIF data.
     */
    public function testGetImageOrientationReturnsDefaultForUnsupportedMime(): void
    {
        $helper = new ImageHelper();
        $path = $this->fixture('sample.png');

        $this->assertSame(1, $helper->getImageOrientation($path, 'image/png', filesize($path)));
    }

    /**
     * 14) A file exceeding the EXIF size limit returns the default
     * orientation, skipping EXIF parsing entirely.
     */
    public function testGetImageOrientationSkipsExifForOversizedFile(): void
    {
        $helper = new ImageHelper();
        $path = $this->fixture('landscape.jpg');

        $tooLarge = 5 * 1024 * 1024 + 1;

        $this->assertSame(1, $helper->getImageOrientation($path, 'image/jpeg', $tooLarge));
    }

    /**
     * 15) A size of 0 returns the default orientation.
     */
    public function testGetImageOrientationReturnsDefaultForZeroSize(): void
    {
        $helper = new ImageHelper();

        $this->assertSame(1, $helper->getImageOrientation($this->fixture('landscape.jpg'), 'image/jpeg', 0));
    }
}
