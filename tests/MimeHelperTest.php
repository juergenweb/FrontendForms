<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\MimeHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MimeHelper.
 *
 * getMimeType()/getMagicBytes() are exercised against the real fixture
 * files already used elsewhere in this test suite, since genuine file
 * content is what finfo/magic-byte detection actually needs.
 */
final class MimeHelperTest extends TestCase
{
    private function fixture(string $name): string
    {
        return __DIR__ . '/fixtures/' . $name;
    }

    // --- getMimeType() ---

    /**
     * 1) A real PNG file is correctly detected via finfo.
     */
    public function testGetMimeTypeDetectsPng(): void
    {
        $helper = new MimeHelper();

        $this->assertSame('image/png', $helper->getMimeType($this->fixture('sample.png')));
    }

    /**
     * 2) A real JPEG file is correctly detected via finfo.
     */
    public function testGetMimeTypeDetectsJpeg(): void
    {
        $helper = new MimeHelper();

        $this->assertSame('image/jpeg', $helper->getMimeType($this->fixture('sample.jpg')));
    }

    /**
     * 3) A real PDF file is correctly detected via finfo.
     */
    public function testGetMimeTypeDetectsPdf(): void
    {
        $helper = new MimeHelper();

        $this->assertSame('application/pdf', $helper->getMimeType($this->fixture('sample.pdf')));
    }

    /**
     * 4) A non-existent file path returns null rather than throwing.
     */
    public function testGetMimeTypeReturnsNullForNonExistentFile(): void
    {
        $helper = new MimeHelper();

        $this->assertNull($helper->getMimeType($this->fixture('this-file-does-not-exist.xyz')));
    }

    /**
     * 5) Passing neither a file path nor content returns null.
     */
    public function testGetMimeTypeReturnsNullForNoInput(): void
    {
        $helper = new MimeHelper();

        $this->assertNull($helper->getMimeType(null, null));
    }

    /**
     * 6) Raw content can be inspected directly, without a file path.
     */
    public function testGetMimeTypeDetectsFromRawContent(): void
    {
        $helper = new MimeHelper();

        $content = file_get_contents($this->fixture('sample.png'));

        $this->assertSame('image/png', $helper->getMimeType(null, $content));
    }

    // --- getMagicBytes() ---

    /**
     * 7) REGRESSION TEST for the fixed '0x'-prefix bug: the magic bytes of
     * a real PNG file, read from disk, correctly start with its signature
     * (without a "0x" prefix) - confirming normalizeMimeType()'s
     * signature comparison can now actually succeed.
     */
    public function testGetMagicBytesMatchesRealPngSignature(): void
    {
        $helper = new MimeHelper();

        $magicBytes = $helper->getMagicBytes($this->fixture('sample.png'));

        $this->assertNotNull($magicBytes);
        $this->assertStringStartsWith('89504E470D0A1A0A', $magicBytes);
    }

    /**
     * 8) Magic bytes can also be read directly from raw content.
     */
    public function testGetMagicBytesReadsFromContent(): void
    {
        $helper = new MimeHelper();

        $jpegBytes = "\xFF\xD8\xFF" . str_repeat("\x00", 20);

        $magicBytes = $helper->getMagicBytes(null, $jpegBytes);

        $this->assertStringStartsWith('FFD8FF', $magicBytes);
    }

    /**
     * 9) A length of 0 or less returns null.
     */
    public function testGetMagicBytesReturnsNullForInvalidLength(): void
    {
        $helper = new MimeHelper();

        $this->assertNull($helper->getMagicBytes(null, 'somecontent', 0));
    }

    // --- normalizeMimeType() ---

    /**
     * 10) A safe, already-known MIME type is returned unchanged.
     */
    public function testNormalizeMimeTypeReturnsSafeTypeUnchanged(): void
    {
        $helper = new MimeHelper();

        $this->assertSame('image/png', $helper->normalizeMimeType('image/png'));
    }

    /**
     * 11) REGRESSION TEST for the fixed '0x'-prefix bug: an "unsafe" MIME
     * type falls back to real magic-byte detection and now correctly
     * identifies a genuine PNG file's content.
     */
    public function testNormalizeMimeTypeFallsBackToMagicBytesForUnsafeType(): void
    {
        $helper = new MimeHelper();

        $pngBytes = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" . str_repeat("\x00", 32);

        $result = $helper->normalizeMimeType('application/octet-stream', null, $pngBytes);

        $this->assertSame('image/png', $result);
    }

    /**
     * 12) A ZIP file's magic bytes are correctly identified through the
     * fallback path.
     */
    public function testNormalizeMimeTypeFallsBackToMagicBytesForZip(): void
    {
        $helper = new MimeHelper();

        $zipBytes = "\x50\x4B\x03\x04" . str_repeat("\x00", 20);

        $result = $helper->normalizeMimeType('application/octet-stream', null, $zipBytes);

        $this->assertSame('application/zip', $result);
    }

    /**
     * 12b) REGRESSION TEST: signatures that are purely numeric strings
     * without leading zeros (e.g. GIF's "474946383761", MP3's "494433")
     * get silently cast to PHP integers as array keys. The fallback
     * detection must still work correctly for these, without a
     * str_starts_with() TypeError.
     */
    public function testNormalizeMimeTypeHandlesNumericSignatureKeys(): void
    {
        $helper = new MimeHelper();

        $gifBytes = 'GIF87a' . str_repeat("\x00", 20);
        $this->assertSame('image/gif', $helper->normalizeMimeType('application/octet-stream', null, $gifBytes));

        $mp3Bytes = 'ID3' . str_repeat("\x00", 20);
        $this->assertSame('audio/mpeg', $helper->normalizeMimeType('application/octet-stream', null, $mp3Bytes));
    }
    public function testNormalizeMimeTypeDetectsSvgContent(): void
    {
        $helper = new MimeHelper();

        $svgContent = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"></svg>';

        $result = $helper->normalizeMimeType('application/octet-stream', null, $svgContent);

        $this->assertSame('image/svg+xml', $result);
    }

    /**
     * 14) SVG content containing a DOCTYPE/ENTITY declaration is rejected
     * by the SVG sniff, as a defensive measure against XXE-style tricks.
     */
    public function testNormalizeMimeTypeRejectsSvgWithDoctype(): void
    {
        $helper = new MimeHelper();

        $maliciousSvg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg></svg>';

        $result = $helper->normalizeMimeType('application/octet-stream', null, $maliciousSvg);

        $this->assertNull($result);
    }

    /**
     * 15) Completely unrecognizable content returns null.
     */
    public function testNormalizeMimeTypeReturnsNullForUnrecognizableContent(): void
    {
        $helper = new MimeHelper();

        $result = $helper->normalizeMimeType('application/octet-stream', null, 'just plain random text here');

        $this->assertNull($result);
    }

    // --- getAllValidExtensions() ---

    /**
     * 16) A known MIME type returns its real, configured extension list.
     */
    public function testGetAllValidExtensionsReturnsKnownExtensions(): void
    {
        $helper = new MimeHelper();

        $this->assertSame(['png'], $helper->getAllValidExtensions('image/png'));
        $this->assertSame(['pdf'], $helper->getAllValidExtensions('application/pdf'));
    }

    /**
     * 17) An unknown MIME type returns an empty array.
     */
    public function testGetAllValidExtensionsReturnsEmptyArrayForUnknownType(): void
    {
        $helper = new MimeHelper();

        $this->assertSame([], $helper->getAllValidExtensions('application/x-totally-made-up-type'));
    }
}