<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;

/**
 * Contains all spam validation logic.
 *
 * This service is directly used by Valitron rules
 * and therefore follows the Valitron callback signature.
 */
class SpamLogic extends BaseLogic
{
    private SpamHelper $spamHelper;
    private const SLIDER_CAPTCHA_TOLERANCE = 0.0001;

    private ?array $stopwordsCache = null;

    /**
     * Create a new SpamLogic instance.
     *
     * @param SpamHelper $spamHelper Helper dependency for spam scoring.
     */
    public function __construct(SpamHelper $spamHelper)
    {
        parent::__construct();

        $this->spamHelper = $spamHelper;
    }

    /**
     * Validate that the submitted content does not exceed the configured spam score threshold.
     *
     * Empty and non-string values are treated as valid.
     * A threshold of 0 disables spam validation entirely.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $params  [0] int threshold 0–100 (default 50), [1] optional exclusion list.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the spam score is within the allowed threshold.
     *
     * @throws InvalidArgumentException If params are present but invalid.
     */
    public function validateContentForSpam(
        string $_field,
        mixed $value,
        array $params,
        array $_fields
    ): bool {
        // Validate params[0]: threshold must be 0–100
        if (!empty($params)) {
            if (!is_int($params[0]) || $params[0] < 0 || $params[0] > 100) {
                throw new InvalidArgumentException(
                    'Param[0] must be an integer between 0 and 100.'
                );
            }

            // Validate optional exclusion list (params[1])
            if (array_key_exists(1, $params)) {
                if (!is_array($params[1])) {
                    throw new InvalidArgumentException('Param[1] must be an array.');
                }

                if ($params[1] === []) {
                    throw new InvalidArgumentException(
                        'Param[1] cannot be empty. Add items or remove it.'
                    );
                }

                foreach ($params[1] as $item) {
                    if (!is_string($item)) {
                        throw new InvalidArgumentException(
                            'All Param[1] items must be strings.'
                        );
                    }
                }
            }
        }

        // Non-string values cannot be evaluated for spam content
        if (!is_string($value)) {
            return true;
        }

        if ($this->stopwordsCache === null) {
            $config = $this->modules->getConfig('FrontendForms');

            $this->stopwordsCache = isset($config['input_stopwords'])
                ? explode("\n", $config['input_stopwords'])
                : [];
        }

        $threshold = (int) ($params[0] ?? 50);

        // 0 disables spam validation
        if ($threshold === 0) {
            return true;
        }

        $score = $this->spamHelper->calculateContentScore(
            $value,
            $this->stopwordsCache ?: null,
            $params[1] ?? null
        );

        return $score <= (100 - $threshold);
    }

    /**
     * Validate that the submitted CAPTCHA value matches the expected answer.
     * Empty values are treated as valid (required validation is handled separately).
     * Comparison is case-insensitive.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   The submitted CAPTCHA answer.
     * @param array  $params  Rule parameters; $params[0] = expected CAPTCHA value.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value is empty or matches the expected CAPTCHA answer.
     *
     * @throws InvalidArgumentException If the expected CAPTCHA value parameter is missing.
     */
    public function validateCaptcha(
        string $_field,
        mixed $value,
        array $params,
        array $_fields
    ): bool {

        if (!isset($params[0])) {
            throw new InvalidArgumentException(
                'Missing parameter: expected CAPTCHA value.'
            );
        }

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        return strcasecmp($value, (string) $params[0]) === 0;
    }

    /*
     * Validate a slider CAPTCHA by checking the correct coordinates
     */
    public function validateSliderCaptcha(
        string $_field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {

        // check if all necessary params exist
        if (!isset($params[0], $params[1], $params[2])) {
            throw new InvalidArgumentException(
                'Missing parameter for slider CAPTCHA.'
            );
        }

        $xPos = $params[0];
        $yPos = $params[1];
        $id = $params[2];

        if (!is_string($id) || $id === '') {
            return false;
        }

        if (!is_numeric($xPos) || !is_numeric($yPos) || $xPos < 0 || $yPos < 0) {
            return false;
        }

        $sessionXPos = $this->wire('session')->get($id . '-captcha_x') ?? false;
        $sessionYPos = $this->wire('session')->get($id . '-captcha_y') ?? false;

        if (!is_numeric($sessionXPos) || !is_numeric($sessionYPos)) {
            return false;
        }

        $xError = abs((float) $sessionXPos - (float) $xPos);
        $yError = abs((float) $sessionYPos - (float) $yPos);

        if ($xError < self::SLIDER_CAPTCHA_TOLERANCE && $yError < self::SLIDER_CAPTCHA_TOLERANCE) {
            $session = $this->wire('session');
            $session->remove($id . '-captcha_x');
            $session->remove($id . '-captcha_y');

            return true;
        }

        return false;

    }

}