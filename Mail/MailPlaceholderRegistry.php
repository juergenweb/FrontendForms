<?php

declare(strict_types=1);

namespace FrontendForms;

use function ProcessWire\wire;

/**
 * MailPlaceholderRegistry
 *
 * Holds the name => value pairs of placeholders that can be used inside
 * email templates or mail body texts (e.g. [[TITLE]], [[BODY]]). Pure data
 * container - the actual replacement inside a template happens in
 * MailTemplateRenderer via wirePopulateStringTags().
 *
 * @package FrontendForms\Mail
 */
final class MailPlaceholderRegistry
{
    private array $placeholders = [];

    /**
     * Set a placeholder value. Array values are flattened to a comma-separated
     * string (handles both simple lists and multi-dimensional $_FILES-style arrays).
     * @param string $name
     * @param string|array|null $value
     * @return void
     */
    public function set(string $name, string|array|null $value): void
    {
        if (is_null($value)) {
            return;
        }

        $name = strtoupper(trim($name));

        if (is_array($value)) {
            // check if the array is multidimensional like multiple file uploads
            if (count($value) == count($value, COUNT_RECURSIVE)) {
                // one-dimensional: convert the array of values to comma separated string
                $value = implode(', ', $value);
            } else {
                $fileNames = [];
                // multi-dimensional $_FILES array
                foreach ($value as $file) {
                    // adding all file names to the array - independent if the name exists or not
                    $fileNames[] = $file['name'];
                }
                // clean the array by removing empty array elements
                $value = implode(',', array_filter($fileNames));
            }
        }

        $this->placeholders[$name] = trim($value);
    }

    /**
     * Remove a placeholder by its name if present
     * @param string $name
     * @return void
     */
    public function remove(string $name): void
    {
        unset($this->placeholders[strtoupper(trim($name))]);
    }

    /**
     * Get the value of a single placeholder by its name
     * @param string $name
     * @return string
     */
    public function get(string $name): string
    {
        return $this->placeholders[strtoupper(trim($name))] ?? '';
    }

    /**
     * Get all placeholders as name => value pairs
     * @return array
     */
    public function all(): array
    {
        return $this->placeholders;
    }

    /**
     * Get all placeholders as name => value pairs, with every value run
     * through HTML Purifier (ProcessWire's MarkupHTMLPurifier module) -
     * for use when populating an HTML context (e.g. an HTML email body),
     * so that user-controlled placeholder values (a submitted field's
     * value, the browser/user-agent string, ...) cannot inject malicious
     * markup or script into the rendered email.
     *
     * Unlike plain htmlspecialchars() escaping, HTML Purifier sanitizes
     * rather than blindly escapes: dangerous markup (<script>, event
     * handler attributes like onclick, javascript: URLs, ...) is
     * stripped out, while safe, well-formed HTML (e.g. a
     * <a href="...">...</a> link the module or a developer intentionally
     * places in a placeholder via Form::setMailPlaceholder()) passes
     * through intact. This means setMailPlaceholder() itself remains
     * safe to use directly for placeholders that legitimately need to
     * contain HTML - no separate "raw"/unescaped variant is needed for
     * that purpose, since setMailPlaceholder() is a public API that
     * existing developer code already relies on behaving this way.
     *
     * "BODY" is always excluded: it holds the already-assembled HTML
     * body content (the template structure itself), not a single
     * user-controlled value - running the full document/fragment through
     * the purifier again here would be redundant (its own content was
     * already populated through this same mechanism) and risks altering
     * email-specific markup patterns Purifier doesn't need to see twice.
     *
     * If the MarkupHTMLPurifier module (part of ProcessWire core, but
     * optional/not installed by default) is not installed, this falls
     * back to plain htmlspecialchars() escaping instead - the same,
     * already-proven-safe behavior used before HTML Purifier support was
     * added here. This means links/HTML placed in a placeholder would
     * not render correctly in that case (the same limitation the
     * previous, htmlspecialchars()-only implementation always had), but
     * no unsanitized markup can ever reach the rendered email - failing
     * safe rather than failing open. A warning is logged (once per
     * request) so this fallback doesn't go unnoticed, since installing
     * MarkupHTMLPurifier is the fix.
     * @return array
     */
    public function allSanitized(): array
    {
        $alwaysUnsanitized = ['BODY'];
        $modules = wire('modules');
        $purifier = $modules->isInstalled('MarkupHTMLPurifier')
            ? $modules->get('MarkupHTMLPurifier')
            : null;

        if (!$purifier) {
            static $warned = false;
            if (!$warned) {
                $warned = true;
                wire('log')->save(
                    'errors',
                    'FrontendForms: MarkupHTMLPurifier is not installed - falling back to plain'
                    . ' htmlspecialchars() escaping for email placeholders, which will break any'
                    . ' placeholder value that intentionally contains HTML (e.g. a link). Install'
                    . ' the core MarkupHTMLPurifier module to fix this (Modules > Core).'
                );
            }
        }

        $sanitized = [];
        foreach ($this->placeholders as $name => $value) {
            if (in_array($name, $alwaysUnsanitized, true)) {
                $sanitized[$name] = $value;
            } elseif ($purifier) {
                $sanitized[$name] = $purifier->purify((string) $value);
            } else {
                $sanitized[$name] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }
}
