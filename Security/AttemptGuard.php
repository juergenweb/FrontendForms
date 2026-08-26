<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WireLog;

/**
 * Guard that checks whether the maximum number of failed attempts has
 * been reached, and logs failed attempts once the limit is hit.
 */
class AttemptGuard extends BaseGuard
{
    /**
     * Write a log entry once the maximum number of failed login attempts
     * has been reached.
     *
     * @return void
     *
     * @throws WireException
     */
    protected function writeLogFailedAttempts(): void
    {
        (new WireLog())->save('failed-attempts-frontendforms', json_encode([
            'FormID' => $this->form->getID(),
            'IP' => $this->wire('session')->getIP(),
        ]));
    }

    /**
     * Check whether the maximum number of allowed attempts has been reached.
     *
     * If $attempts sanitizes to 0 (no failed attempts recorded yet), the
     * check trivially passes without needing to compare against the
     * configured maximum.
     *
     * @param int|string|bool|null $attempts The current number of failed
     *                                        attempts recorded in the session.
     *
     * @return bool True if the attempts limit has not been reached, false otherwise.
     *
     * @throws WireException
     */
    public function check(int|string|bool|null $attempts): bool
    {
        $attempts = $this->wire('sanitizer')->int($attempts);

        if (!$attempts) {
            return true;
        }

        if (($this->form->getMaxAttempts() - $attempts) > 0) {
            return true;
        }

        if ($this->form->getLogFailedAttempts()) {
            $this->writeLogFailedAttempts();
        }

        $this->wire('session')->set('blocked', 'maxAttempts');

        return false;
    }
}