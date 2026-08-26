<?php

declare(strict_types=1);

namespace FrontendForms;

use finfo;
use RuntimeException;

/**
 * Helper class for MIME type detection, normalization,
 * magic byte analysis, and extension validation.
 *
 * Uses PHP finfo, magic byte signatures, and caching.
 */
class MimeHelper extends BaseHelper
{
    /**
     * MIME types considered unsafe or unreliable.
     */
    private const UNSAFE = [
        'application/octet-stream',
        'application/x-empty',
    ];

    /**
     * Known magic byte signatures mapped to MIME types.
     */
    private const MAGIC_SIGNATURES = [
        '89504E470D0A1A0A' => 'image/png',
        'FFD8FF' => 'image/jpeg',
        '474946383761' => 'image/gif',
        '474946383961' => 'image/gif',
        '49492A00' => 'image/tiff',
        '4D4D002A' => 'image/tiff',
        '00000100' => 'image/x-icon',

        '25504446' => 'application/pdf',

        '504B0304' => 'application/zip',
        '526172211A0700' => 'application/x-rar-compressed',
        '377ABCAF271C' => 'application/x-7z-compressed',
        '1F8B08' => 'application/gzip',

        '1A45DFA3' => 'video/webm',
        '494433' => 'audio/mpeg',
    ];

    /**
     * Maximum number of cached MIME entries.
     */
    private const MIME_CACHE_MAX = 1000;

    /**
     * Maximum file size for finfo analysis.
     */
    private const MAX_FINFO_FILE_SIZE = 100 * 1024 * 1024;

    /**
     * Shared finfo instance.
     */
    private static ?finfo $finfo = null;

    /**
     * Cached MIME results.
     *
     * @var array<string, string|null>
     */
    private static array $mimeCache = [];

    /**
     * Path to MIME mapping JSON file.
     */
    private string $mimeMapPath;

    /**
     * Loaded MIME mapping data, cached statically per path so the JSON
     * file is parsed only once even if multiple MimeHelper instances exist.
     *
     * @var array<string, array>
     */
    private static array $mimeTypesJSONCache = [];

    /**
     * Cached magic bytes.
     */
    private static array $magicBytesCache = [];

    /**
     * Create a new MimeHelper instance and load (or reuse the cached) MIME map.
     */
    public function __construct()
    {
        parent::__construct();

        $this->mimeMapPath =
            $this->config->paths->siteModules
            . 'FrontendForms/data/mimetypes.json';

        self::$mimeTypesJSONCache[$this->mimeMapPath] ??= $this->loadMimeTypeFile();
    }

    /**
     * Detect the MIME type of a file (by path) or raw content, using
     * PHP's finfo extension with per-file result caching.
     *
     * @param string|null $filePath Path to the file to inspect, or null.
     * @param string|null $content  Raw file content to inspect, or null.
     *
     * @return string|null The detected MIME type, or null if undetectable/unsafe.
     */
    public function getMimeType(
        ?string $filePath = null,
        ?string $content = null
    ): ?string {
        if ($filePath === null && $content === null) {
            return null;
        }

        $cacheKey = null;

        if ($filePath !== null) {
            $stat = $this->getFileStat($filePath);

            if ($stat === null) {
                return null;
            }

            $cacheKey = $filePath . ':' . $stat['mtime'] . ':' . $stat['size'];

            if (array_key_exists($cacheKey, self::$mimeCache)) {
                return self::$mimeCache[$cacheKey];
            }

            if ($stat['size'] > self::MAX_FINFO_FILE_SIZE) {
                return null;
            }
        }

        $finfo = $this->getFinfoHandle();

        $raw = $filePath !== null
            ? finfo_file($finfo, $filePath)
            : finfo_buffer($finfo, substr($content, 0, 8192));

        if (!is_string($raw) || $raw === '') {
            if ($cacheKey !== null) {
                $this->cacheSet($cacheKey, null);
            }

            return null;
        }

        $mime = explode(';', strtolower(trim($raw)), 2)[0];
        $safeResult = in_array($mime, self::UNSAFE, true) ? null : $mime;

        if ($cacheKey !== null) {
            $this->cacheSet($cacheKey, $safeResult);
        }

        return $safeResult;
    }

    /**
     * Normalize an unreliable or unsafe MIME type by falling back to
     * magic-byte signature detection, and finally SVG content sniffing.
     *
     * @param string|null $mimeType   MIME type previously detected (e.g. via finfo).
     * @param string|null $filePath   Path to the file, used for magic byte detection.
     * @param string|null $content    Raw file content, used for magic byte/SVG detection.
     * @param array|null  $stat       Pre-fetched file stat data (mtime/size), if available.
     * @param string|null $magicBytes Pre-computed magic bytes, if available.
     *
     * @return string|null The normalized MIME type, or null if it could not be determined.
     */
    public function normalizeMimeType(
        ?string $mimeType,
        ?string $filePath = null,
        ?string $content = null,
        ?array $stat = null,
        ?string $magicBytes = null
    ): ?string {
        if ($mimeType !== null && !in_array($mimeType, self::UNSAFE, true)) {
            return $mimeType;
        }

        $magicBytes ??= $this->getMagicBytes(
            filePath: $filePath,
            content: $content,
            stat: $stat
        );

        if ($magicBytes !== null) {
            foreach (self::MAGIC_SIGNATURES as $signature => $detectedMime) {
                if (str_starts_with($magicBytes, (string) $signature)) {
                    return $detectedMime;
                }
            }

            if (
                str_starts_with($magicBytes, '52494646')
                && strlen($magicBytes) >= 24
                && substr($magicBytes, 16, 8) === '57454250'
            ) {
                return 'image/webp';
            }

            if (
                strlen($magicBytes) >= 16
                && substr($magicBytes, 8, 8) === '66747970'
            ) {
                return 'video/mp4';
            }
        }

        if ($content !== null) {
            $svgSample = strtolower(trim(substr($content, 0, 1024)));

            $hasDoctype =
                str_contains($svgSample, '<!doctype')
                || str_contains($svgSample, '<!entity');

            if (
                !$hasDoctype
                && preg_match('/<svg\b/i', $svgSample) === 1
            ) {
                return 'image/svg+xml';
            }
        }

        return null;
    }

    /**
     * Return the list of valid file extensions for a given MIME type,
     * as defined in the loaded MIME mapping file.
     *
     * @param string $mimeType The MIME type to look up.
     *
     * @return string[] Valid extensions for the MIME type, or an empty array if unknown.
     */
    public function getAllValidExtensions(string $mimeType): array
    {
        return self::$mimeTypesJSONCache[$this->mimeMapPath][$mimeType]['extensions'] ?? [];
    }

    /**
     * Retrieve the mtime and size of a readable file, used as a cache key
     * fingerprint for MIME type and magic byte caching.
     *
     * @param string $filePath Path to the file.
     *
     * @return array{mtime: int, size: int}|null File stat data, or null if unreadable.
     */
    private function getFileStat(string $filePath): ?array
    {
        if (
            $filePath === ''
            || !is_file($filePath)
            || !is_readable($filePath)
        ) {
            return null;
        }

        $stat = @stat($filePath);

        if ($stat === false || !isset($stat['mtime'], $stat['size'])) {
            return null;
        }

        return [
            'mtime' => $stat['mtime'],
            'size' => $stat['size'],
        ];
    }

    /**
     * Read and hex-encode the leading magic bytes of a file or content
     * buffer, with per-file result caching.
     *
     * @param string|null $filePath Path to the file to read, or null.
     * @param string|null $content  Raw content to read, or null.
     * @param int         $length   Number of leading bytes to read.
     * @param array|null  $stat     Pre-fetched file stat data, if available.
     *
     * @return string|null Uppercase hex string of the magic bytes, or null.
     */
    public function getMagicBytes(
        ?string $filePath = null,
        ?string $content = null,
        int $length = 16,
        ?array $stat = null
    ): ?string {
        if ($filePath === null && $content === null) {
            return null;
        }

        if ($length <= 0) {
            return null;
        }

        $cacheKey = null;

        if ($filePath !== null) {
            $stat ??= $this->getFileStat($filePath);

            if ($stat === null) {
                return null;
            }

            $cacheKey = $filePath . ':' . $stat['mtime'] . ':' . $stat['size'];

            if (array_key_exists($cacheKey, self::$magicBytesCache)) {
                return self::$magicBytesCache[$cacheKey];
            }

            $handle = fopen($filePath, 'rb');

            if ($handle === false) {
                return null;
            }

            try {
                $raw = fread($handle, $length);
            } finally {
                fclose($handle);
            }
        } else {
            if ($content === '') {
                return null;
            }

            $raw = substr($content, 0, $length);
        }

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $result = strtoupper(bin2hex($raw));

        if ($cacheKey !== null) {
            self::$magicBytesCache[$cacheKey] = $result;
        }

        return $result;
    }

    /**
     * Return a shared, lazily-initialized finfo handle for MIME detection.
     *
     * @return finfo The shared finfo instance.
     *
     * @throws RuntimeException If the finfo extension cannot be initialized.
     */
    private function getFinfoHandle(): finfo
    {
        if (self::$finfo === null) {
            $handle = finfo_open(FILEINFO_MIME_TYPE);

            if ($handle === false) {
                throw new RuntimeException('finfo_open() failed');
            }

            self::$finfo = $handle;
        }

        return self::$finfo;
    }

    /**
     * Store a MIME detection result in the static cache, evicting the
     * oldest entry first if the cache size limit has been reached.
     *
     * @param string      $key   Cache key (file path + mtime + size).
     * @param string|null $value The MIME type to cache, or null.
     */
    private function cacheSet(string $key, ?string $value): void
    {
        if (count(self::$mimeCache) >= self::MIME_CACHE_MAX) {
            $oldestKey = array_key_first(self::$mimeCache);

            if ($oldestKey !== null) {
                unset(self::$mimeCache[$oldestKey]);
            }
        }

        self::$mimeCache[$key] = $value;
    }

    /**
     * Load and parse the MIME mapping JSON file from disk.
     *
     * @return array The decoded MIME mapping data.
     *
     * @throws RuntimeException If the file cannot be read or parsed.
     */
    private function loadMimeTypeFile(): array
    {
        $json = file_get_contents($this->mimeMapPath);

        if ($json === false) {
            throw new RuntimeException(
                "Error loading JSON file from: {$this->mimeMapPath}"
            );
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Error parsing JSON file: ' . json_last_error_msg()
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'MIME mapping file must contain a JSON object.'
            );
        }

        return $data;
    }
}