<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Guard that checks whether a visitor's IP address is on the configured
 * IP blacklist, preventing banned visitors from seeing/submitting the form.
 */
class IPBlacklistGuard extends BaseGuard
{
    /**
     * Check whether the given visitor IP is allowed to view/submit the form.
     *
     * @param bool   $useIPBan       Whether the IP ban feature is enabled at all.
     * @param string $preventIPsList Newline-separated list of banned IP addresses.
     * @param string $visitorIP      The current visitor's IP address.
     *
     * @return bool True if the visitor is NOT on the blacklist (allowed to view the form).
     */
    public function check(bool $useIPBan, string $preventIPsList, string $visitorIP): bool
    {
        if (!$useIPBan) {
            return true;
        }
        if ($preventIPsList === '') {
            return true;
        }
        $ipAddresses = $this->newLineToArray($preventIPsList);
        return !in_array($visitorIP, $ipAddresses, true);
    }

    /**
     * Convert the values of a textarea (one entry per line) to a trimmed array.
     *
     * @param string|null $textarea The value of the textarea field.
     *
     * @return array
     */
    protected function newLineToArray(string|null $textarea = null): array
    {
        $final_array = [];
        if (!is_null($textarea)) {
            $textarea_array = array_map('trim', explode("\n", $textarea)); // remove extra spaces from each array value
            foreach ($textarea_array as $textarea_arr) {
                $final_array[] = trim($textarea_arr);
            }
        }
        return $final_array;
    }
}