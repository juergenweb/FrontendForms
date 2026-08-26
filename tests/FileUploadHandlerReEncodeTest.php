<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FileUploadHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for FileUploadHandler::reEncodeIfImage() - the hardening step
 * that re-encodes an uploaded image through GD, discarding the original
 * file bytes (and anything hidden inside them) in favor of a freshly
 * rendered copy of the decoded pixel data.
 *
 * This is pure file/GD logic with no wire()/Form dependency (unlike
 * storeUploadedFiles(), which needs a genuinely HTTP-uploaded file for
 * move_uploaded_file() to accept it - not reproducible with a plain temp
 * file in a unit test, so that method's chmod()/mkdir()-recursive
 * behavior is left to integration testing, consistent with how other
 * request-dependent methods are already handled elsewhere in this suite).
 * The handler instance itself is created via
 * newInstanceWithoutConstructor(), since the constructor's Form parameter
 * plays no role in this specific method.
 *
 * Requires the GD extension to be loaded to produce meaningful results;
 * if it's missing, reEncodeIfImage() itself no-ops (see
 * testDoesNothingWithoutGdExtension() below for the one exception, which
 * doesn't require GD at all).
 */
final class FileUploadHandlerReEncodeTest extends TestCase
{
    /** @var string[] Temp files created during the test, removed in tearDown(). */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    private function tempFile(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/frontendforms-reencode-' . uniqid() . $suffix;
        $this->tempFiles[] = $path;
        return $path;
    }

    private function callReEncodeIfImage(string $path): void
    {
        $ref = new ReflectionClass(FileUploadHandler::class);
        $handler = $ref->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(FileUploadHandler::class, 'reEncodeIfImage');
        $method->setAccessible(true);
        $method->invoke($handler, $path);
    }

    /**
     * 1) SECURITY REGRESSION TEST: a "polyglot" file - a genuinely valid
     * JPEG with PHP code appended directly after its own image data,
     * still executable if the file were ever interpreted as PHP - has
     * that appended payload completely stripped after re-encoding, while
     * remaining a valid, correctly-sized image.
     */
    public function testStripsPayloadAppendedAfterValidJpegData(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $path = $this->tempFile('.jpg');
        $im = imagecreatetruecolor(20, 15);
        imagejpeg($im, $path, 90);
        imagedestroy($im);

        file_put_contents($path, "<?php system(\$_GET['cmd']); ?>", FILE_APPEND);
        $this->assertStringContainsString('system', file_get_contents($path));

        $this->callReEncodeIfImage($path);

        $this->assertStringNotContainsString('system', file_get_contents($path));
        $info = getimagesize($path);
        $this->assertNotFalse($info);
        $this->assertSame(20, $info[0]);
        $this->assertSame(15, $info[1]);
    }

    /**
     * 2) A non-image file (recognized neither by extension nor by actual
     * content) is left completely untouched, byte for byte.
     */
    public function testLeavesNonImageFileUntouched(): void
    {
        $path = $this->tempFile('.pdf');
        $original = '%PDF-1.4 not a real pdf, just plain text content for testing';
        file_put_contents($path, $original);

        $this->callReEncodeIfImage($path);

        $this->assertSame($original, file_get_contents($path));
    }

    /**
     * 3) PNG transparency survives the re-encode instead of being
     * silently flattened onto a solid background.
     */
    public function testPreservesPngTransparency(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $path = $this->tempFile('.png');
        $im = imagecreatetruecolor(10, 10);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
        imagepng($im, $path);
        imagedestroy($im);

        $this->callReEncodeIfImage($path);

        $reloaded = imagecreatefrompng($path);
        $this->assertNotFalse($reloaded);
        $rgba = imagecolorat($reloaded, 5, 5);
        $alpha = ($rgba >> 24) & 0x7F;
        imagedestroy($reloaded);

        $this->assertSame(127, $alpha, 'Fully transparent pixel should still be fully transparent (alpha=127) after re-encoding.');
    }

    /**
     * 4) GIF files are also re-encoded correctly (not just JPEG/PNG),
     * remaining valid, correctly-sized images afterwards.
     */
    public function testReEncodesGifCorrectly(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $path = $this->tempFile('.gif');
        $im = imagecreatetruecolor(12, 8);
        imagegif($im, $path);
        imagedestroy($im);

        $this->callReEncodeIfImage($path);

        $info = getimagesize($path);
        $this->assertNotFalse($info);
        $this->assertSame(IMAGETYPE_GIF, $info[2]);
        $this->assertSame(12, $info[0]);
        $this->assertSame(8, $info[1]);
    }

    /**
     * 5) A file with a recognizable image header but truncated/corrupted
     * body (getimagesize() succeeds, but the full image can't actually be
     * decoded) is left as-is rather than being corrupted further or
     * deleted.
     */
    public function testLeavesCorruptedImageUntouched(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $path = $this->tempFile('.jpg');
        $im = imagecreatetruecolor(50, 50);
        imagejpeg($im, $path, 90);
        imagedestroy($im);

        // truncate to just the first few header bytes - still enough for
        // getimagesize() to recognize it as a JPEG, but not enough for
        // imagecreatefromjpeg() to fully decode it
        $original = file_get_contents($path);
        $truncated = substr($original, 0, 20);
        file_put_contents($path, $truncated);

        $this->callReEncodeIfImage($path);

        $this->assertSame($truncated, file_get_contents($path));
    }

    /**
     * 6) An empty file is left untouched rather than causing an error -
     * getimagesize() returns false for it, so reEncodeIfImage() returns
     * immediately without ever reaching the GD calls. Does not require
     * GD to be loaded.
     */
    public function testLeavesEmptyFileUntouched(): void
    {
        $path = $this->tempFile('.jpg');
        file_put_contents($path, '');

        $this->callReEncodeIfImage($path);

        $this->assertSame('', file_get_contents($path));
    }
}
