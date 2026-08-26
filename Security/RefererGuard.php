<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Guard that provides a lightweight, defense-in-depth check on top of CSRF
 * protection: verifies that the Referer header (if present) points to the
 * site's own host.
 *
 * This is intentionally NOT a hard requirement: many browsers, privacy
 * extensions, VPNs, and corporate proxies legitimately strip the Referer
 * header entirely. A missing Referer header is therefore treated as
 * inconclusive (allowed), while a Referer pointing to a different host is
 * treated as a strong signal of a cross-site request and rejected.
 */
class RefererGuard extends BaseGuard
{
    /**
     * Check whether the current request's Referer header (if present)
     * points to this site's own host.
     *
     * @return bool True if the Referer is missing or matches this site's host,
     *              false if it points to a different host.
     */
    public function check(): bool
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referer === '') {
            // Referer header missing entirely - do not block on absence,
            // only on an explicit mismatch (see class docblock).
            return true;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        $siteHost = $this->wire('config')->httpHost;

        return is_string($refererHost) && strcasecmp($refererHost, $siteHost) === 0;
    }
}