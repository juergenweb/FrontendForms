<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Helper class for image analysis and EXIF orientation handling.
 *
 * This class validates image files, extracts dimensions,
 * and reads EXIF orientation metadata for supported image formats.
 */
class ImageHelper extends BaseHelper
{
    /**
     * MIME types that support EXIF orientation metadata.
     */
    private const EXIF_MIME_TYPES = [
        'image/jpeg',
        'image/tiff',
    ];

    /**
     * Maximum image size allowed for analysis.
     *
     * Prevents excessive memory usage when analyzing large images.
     */
    public const MAX_IMAGE_ANALYZE_SIZE = 20 * 1024 * 1024;

    /**
     * ImageHelper constructor.
     */
    public function __construct()
    {
    }

    /**
     * Verify whether a file is a valid image.
     *
     * Returns the raw getimagesize() result array for raster images,
     * an empty array for SVG (which cannot be read by getimagesize()),
     * or false if the file is not a valid image.
     *
     * @param int         $size     File size in bytes.
     * @param string|null $mimeType Detected MIME type.
     * @param string|null $content  Raw file content (used instead of path when available).
     * @param string      $path     Path to the file on disk.
     *
     * @return array|false Raw image info array, empty array for SVG, or false if not an image.
     */
    public function detectImage(
        int $size,
        ?string $mimeType,
        ?string $content = null,
        string $path = ''
    ): array|false {
        if ($size <= 0 || $size > self::MAX_IMAGE_ANALYZE_SIZE) {
            return false;
        }

        if ($mimeType === null || !str_starts_with($mimeType, 'image/')) {
            return false;
        }

        // SVG cannot be read by getimagesize() — MIME type is sufficient here,
        // as SVG content is already validated by MimeHelper.
        if ($mimeType === 'image/svg+xml') {
            return [];
        }

        // For raster images, verify the actual content.
        $img = $content !== null
            ? @getimagesizefromstring($content)
            : @getimagesize($path);

        return $img !== false ? $img : false;
    }

    /**
     * Analyze an image file and return its metadata.
     *
     * Extracts image dimensions and EXIF orientation data.
     * Width and height are automatically swapped for rotated images.
     *
     * @param string      $path Path to the image file on disk.
     * @param string|null $mime Detected MIME type of the image.
     * @param int         $size File size in bytes.
     * @param array       $img  Result returned by detectImage().
     *
     * @return array{
     *     width: int,
     *     height: int,
     *     orientation: int
     * }|null
     */
    public function analyzeImage(
        string $path,
        ?string $mime,
        int $size,
        array $img
    ): ?array {
        if ($mime === null) {
            return null;
        }

        // SVG has no pixel dimensions.
        if ($mime === 'image/svg+xml') {
            return [
                'width' => 0,
                'height' => 0,
                'orientation' => 1,
            ];
        }

        // Empty $img means detectImage() returned no usable data.
        if ($img === []) {
            return null;
        }

        $orientation = $this->getImageOrientation($path, $mime, $size);

        $width = $img[0];
        $height = $img[1];

        // Swap dimensions for 90° / 270° rotated images.
        if (in_array($orientation, [5, 6, 7, 8], true)) {
            [$width, $height] = [$height, $width];
        }

        return [
            'width' => $width,
            'height' => $height,
            'orientation' => $orientation,
        ];
    }

    /**
     * Return the EXIF orientation value of an image.
     *
     * Returns 1 if no valid EXIF orientation data exists,
     * if the image format does not support EXIF metadata,
     * or if the file exceeds the size limit for EXIF parsing.
     *
     * @param string $filePath Path to the image file.
     * @param string $mime     MIME type of the image.
     * @param int    $size     File size in bytes.
     */
    public function getImageOrientation(
        string $filePath,
        string $mime,
        int $size
    ): int {
        // Skip EXIF parsing for large files to prevent DoS.
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            return 1;
        }

        // Only JPEG and TIFF support EXIF orientation.
        if (!in_array($mime, self::EXIF_MIME_TYPES, true)) {
            return 1;
        }

        $exif = @exif_read_data($filePath);

        if (!is_array($exif) || !isset($exif['Orientation'])) {
            return 1;
        }

        $orientation = (int) $exif['Orientation'];

        return $orientation !== 0 ? $orientation : 1;
    }
}