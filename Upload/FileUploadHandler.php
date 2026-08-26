<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * FileUploadHandler
 *
 * Handles the storage side of file uploads after successful validation:
 * rearranging the raw $_FILES structure for multi-file inputs, moving
 * validated files into the configured upload folder, and tracking the
 * resulting file paths.
 *
 * Note: this class is only responsible for post-validation storage.
 * Validation of uploaded files (allowed extensions, size limits, ZIP
 * content checks, etc.) is handled separately by FileLogic/ZipLogic,
 * which read directly from $_FILES via FileHelper - they do not depend
 * on this class.
 *
 * @package FrontendForms\Upload
 */
final class FileUploadHandler
{
    private string $uploadPath = '';
    private array $uploadedFiles = []; // paths of the files stored during the last storeUploadedFiles() call

    public function __construct(private readonly Form $form)
    {
    }

    /**
     * Set a custom upload path for uploaded files
     * @param string $pathToFolder
     * @return void
     */
    public function setUploadPath(string $pathToFolder): void
    {
        $this->uploadPath = trim($pathToFolder);
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    /**
     * Get all files that were stored during the last storeUploadedFiles() call
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Overwrite the tracked list of uploaded files directly (used for the legacy
     * "overwritten filenames" compatibility path in Form::___isValid()).
     * @param array $files
     * @return void
     */
    public function setUploadedFiles(array $files): void
    {
        $this->uploadedFiles = $files;
    }

    /**
     * Rearrange the multi-file $_FILES sub-array into one array per file
     * instead of one array per property (name, tmp_name, error, ...).
     * @param array $filePost - the $_FILES[$fieldName] sub-array for a multiple-file input
     * @return array
     */
    public function reArrayFiles(array $filePost): array
    {
        $fileArray = [];
        $fileCount = count($filePost['name']);
        $fileKeys = array_keys($filePost);
        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $fileArray[$i][$key] = $filePost[$key][$i];
            }
        }
        return $fileArray;
    }

    /**
     * Re-encode an uploaded image file through GD, discarding the
     * original file bytes entirely and replacing them with a freshly
     * rendered copy containing only the actual pixel data.
     *
     * This neutralizes hidden payloads that rely on the original file
     * bytes surviving the upload unchanged - e.g. a "polyglot" file that
     * is simultaneously a valid image and valid PHP/HTML/JS (code
     * appended after the image's own logical end, which an image viewer
     * ignores but a script interpreter would not), or a script hidden
     * inside EXIF/metadata fields - regardless of the specific technique
     * used, since only the genuine, decoded pixel content survives a
     * full decode+re-encode round-trip.
     *
     * Does nothing (leaves the file untouched) for non-image files, if
     * the file cannot be decoded as a valid image, or if the GD
     * extension is not available - this is an additional hardening
     * layer on top of, not a replacement for, the existing
     * extension/MIME-type validation that already ran before this point.
     * @param string $path Path to the already-stored file
     * @return void
     */
    private function reEncodeIfImage(string $path): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        // reads the actual image header bytes rather than trusting the
        // file extension or the client-supplied MIME type - returns
        // false for anything that isn't a recognized image format
        $imageInfo = @getimagesize($path);
        if ($imageInfo === false) {
            return;
        }

        $type = $imageInfo[2];

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if ($image === false) {
            // getimagesize() recognized a header, but the file could
            // still not actually be decoded (e.g. truncated/corrupted) -
            // leave it as-is rather than deleting it
            return;
        }

        // PNG/GIF/WebP can have transparency - preserve it instead of
        // silently flattening to a solid background, which would
        // visibly corrupt the image
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, 90),
            IMAGETYPE_PNG => imagepng($image, $path),
            IMAGETYPE_GIF => imagegif($image, $path),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($image, $path) : null,
            default => null,
        };

        imagedestroy($image);
    }

    /**
     * Store all uploaded files from InputFile fields inside the form in the
     * configured upload folder. Remembers the resulting paths internally,
     * retrievable via getUploadedFiles().
     * @param array $formElements
     * @return array the paths of the stored files
     */
    public function storeUploadedFiles(array $formElements): array
    {
        $uploadedFiles = [];
        if ($_FILES) {
            // create directory if it does not exist (recursive, in case
            // uploadPath is a multi-level path that doesn't exist yet)
            $this->form->wire('files')->mkdir($this->uploadPath, true);
            // get all upload fields inside the form
            foreach ($formElements as $element) {

                if ($element instanceof InputFile) {
                    $fieldName = $element->getAttribute('name'); // the name of the upload field

                    if ($element->getMultiple()) {
                        // multiple files
                        if (array_key_exists($fieldName, $_FILES)) {
                            $files = $this->reArrayFiles($_FILES[$fieldName]);
                            foreach ($files as $file) {
                                if ($file['error'] == 0) {
                                    // sanitize file name and convert it to lowercase to prevent problems on certain servers
                                    $filename = $this->form->wire('sanitizer')->filename($file['name'], true);
                                    $targetFile = $this->uploadPath . strtolower($filename);
                                    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                                        // explicitly enforce a safe, non-executable
                                        // permission mode, regardless of the
                                        // server's umask/PHP process configuration
                                        chmod($targetFile, 0644);
                                        $uploadedFiles[] = $targetFile;
                                        $this->reEncodeIfImage($targetFile);
                                    }
                                }
                            }
                        }
                    } else {
                        // single file
                        $file = $_FILES[$fieldName];
                        if ($file['error'] == 0) {
                            // sanitize file name and convert it to lowercase to prevent problems on certain servers
                            $filename = $this->form->wire('sanitizer')->filename(basename($file['name']), true);
                            $targetFile = $this->uploadPath . strtolower($filename);
                            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                                // explicitly enforce a safe, non-executable
                                // permission mode, regardless of the
                                // server's umask/PHP process configuration
                                chmod($targetFile, 0644);
                                $uploadedFiles[] = $targetFile;
                                $this->reEncodeIfImage($targetFile);
                            }
                        }
                    }
                }
            }
        }
        $this->uploadedFiles = $uploadedFiles;
        return $uploadedFiles;
    }
}
