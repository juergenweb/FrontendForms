<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\WireException;

/**
 * Guard that checks for CSRF attacks on form submissions.
 */
class CSRFGuard extends BaseGuard
{
    /**
     * Check for a CSRF attack.
     *
     * Any HTTP method other than "post"/"get" is treated as invalid
     * (fail-closed), since only these two methods are supported for
     * CSRF token verification.
     *
     * @param bool   $useCSRFProtection Whether CSRF protection is enabled.
     * @param string $method            The HTTP method used for the submission.
     *
     * @return bool True if CSRF protection is disabled or a valid token is present.
     *
     * @throws WireException
     */
    public function check(bool $useCSRFProtection, string $method): bool
    {
        if (!$useCSRFProtection) {
            return true;
        }

        // sanitize method name to be all lower
        return match (strtolower($method)) {
            'post' => $this->wire('session')->CSRF->hasValidToken(),
            'get' => $this->wire('input')->get($this->wire('session')->CSRF->getTokenName())
                === $this->wire('session')->CSRF->getTokenValue(),
            default => false,
        };
    }
}