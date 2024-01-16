<?php
    declare(strict_types=1);

    /*
     * Abstract class for creating a captcha in different variations
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: AbstractCaptcha.php
     * Created: 05.08.2022
     */

    namespace FrontendForms;

    use ProcessWire\FrontendForms;
    use Exception;
    use GdImage;
    use InvalidArgumentException;
    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    abstract class AbstractCaptcha extends Tag
    {

        protected string $captchaValidValue = ''; // the value from the captcha that should be entered by the user
        protected string $category = ''; // the category type of the captcha (text or image)
        protected string $type = ''; // the name of the captcha

        // General objects for all captcha
        protected Link $reloadLink; // the reload link object for reloading the captcha if needed
        protected Image $captchaImageTag; // the image tag for the captcha image

        /**
         * @throws WireException
         * @throws WirePermissionException
         * @throws Exception
         */
        public function __construct()
        {
            parent::__construct();

            $this->type = $this->className(); // set the name of the captcha from the class name
            // instantiate link an image object
            $this->reloadLink = new Link(); // instantiate reload link object
            $this->captchaImageTag = new Image(); // instantiate the image tag object
        }

        /**
         * Get the color of the distortion lines as RGBa array
         * As fallback value #fff will be used as output
         * @return array
         * @throws Exception
         */
        protected function getLinesColor(): array
        {
            return self::linebreaksValuesToArray($this->frontendforms['input_captchaLinesColor']);
        }

        /**
         * Get the number of lines over the captcha image
         * @return int
         */
        protected function getNumberOfLines(): int
        {
            return (int)$this->frontendforms['input_captchaNumberOfLines'];
        }

        /**
         * Set the number of colors, which should be used for the distortion lines
         * The higher the number, the more colorful the lines
         * @param int $number
         * @return $this
         */
        protected function setNumberOfColors(int $number): self
        {
            $this->frontendforms['input_numberOfColorsOfLines'] = $number;
            return $this;
        }

        /**
         * Get the number of colors that should be used in the distortion lines
         * @return int
         */
        protected function getNumberOfColors(): int
        {
            return $this->frontendforms['input_numberOfColorsOfLines'];
        }

        /**
         * Get the width of the captcha image
         * Needs typecasting because value will be stored as string in the database
         * @return int
         */
        protected function getWidth(): int
        {
            return (int)$this->frontendforms['input_captchaWidth'];
        }

        /**
         * Get the height of the captcha image
         * Needs typecasting because value will be stored as string in the database
         * @return int
         */
        protected function getHeight(): int
        {
            return (int)$this->frontendforms['input_captchaHeight'];
        }

        /**
         * Get the type of the distortion lines (custom or random)
         * @return string
         * @throws WireException
         */
        protected function getLinesType(): string
        {
            if (in_array($this->frontendforms['input_colorchooser'], ['random', 'custom'])) {
                return $this->frontendforms['input_colorchooser'];
            } else {
                // return default value from module configuration instead
                return $this->wire('modules')->getModuleConfigData('FrontendForms')['input_colorchooser'];
            }
        }

        /**
         * Set the real captcha value for input validation
         * This depends on the captcha variant set, so this contains the value that should be entered into the input field
         *
         * @param string $content
         * @return $this
         */
        protected function setCaptchaValidValue(string $content): self
        {
            $this->captchaValidValue = $content;
            return $this;
        }

        /**
         * Get the solution value of the captcha
         * @return string
         */
        protected function getCaptchaValidValue(): string
        {
            return $this->captchaValidValue;
        }

        /**
         * Various helper methods
         * Most of the methods are static, so they can be used outside a class too
         * @throws Exception
         */

        /**
         * Returns an array of red, green blue value as integers
         * @param string $color
         * @return array
         * @throws Exception
         */
        protected function setColor(string $color): array
        {
            // create array of the string, because rgb consist of multiple values
            $colorValues = explode(',', $color);
            // remove every item after position 2 from the array - only 3 items are allowed as max
            $offsetKey = 2; // The offset you need to grab
            $n = array_keys($colorValues); // Grab all the keys of your actual array and put in another array
            $count = array_search($offsetKey, $n); //<--- Returns the position of the offset from this array using search
            $colorValues = array_slice($colorValues, 0, $count + 1,
                true);// Slice it with the 0 index as start and position+1 as the length parameter.
            // grab first value and test if it is HEX color
            if (FrontendForms::checkHex($colorValues[0])) // we have a valid hex value
            {
                return self::hex2rgb($colorValues[0]);
            } // convert it to rgba
            // we have another value, so convert all array values to integer first for rgb check
            return array_map('intval', $colorValues);
        }

        /**
         * Convert hex color string to rgb color array
         * Alpha channel will be ignored if present (no rgba)
         * Source: Based on https://github.com/eislambey/hex2rgba/blob/master/src/hex2rgba.php
         *
         * @param string $hex
         * @return array
         */
        public static function hex2rgb(string $hex): array
        {
            if (str_starts_with($hex, '#')) {
                $hex = ltrim($hex, '#');
            }
            if (strlen($hex) !== 3 && strlen($hex) !== 6) {
                throw new InvalidArgumentException("Invalid hex: $hex");
            }
            if (strlen($hex) === 3) {
                $hex .= $hex;
            }

            [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
            return [(int)$r, (int)$g, (int)$b];
        }

        /*
         * Image manipulation (distortion, noise, ...)
         */

        /**
         * Create array out of string separated by line breaks \n from textarea
         * @param string|null $textarea_value
         * @param string $fallback
         * @return array
         */
        public static function linebreaksValuesToArray(string|null $textarea_value, string $fallback = '#fff'): array
        {
            if ($textarea_value) {
                return array_map('trim', explode("\n", $textarea_value)); // to remove extra spaces from each value of array
            }
            return [$fallback];
        }

        /**
         * Create an array of all line colors depending on the settings
         * @return array
         * @throws WireException
         * @throws Exception
         */
        protected function createRGBColorArray(): array
        {

            $numberOfLines = $this->getNumberOfLines(); // how many lines should be added
            $customColors = $this->getLinesColor(); // array of all custom colors

            $colorList = [];

            if ($numberOfLines > 0) {
                $colors = [];
                if ($this->getLinesType() === 'random') {

                    $numOfColors = ($this->getNumberOfColors() !== 0) ? $this->getNumberOfColors() : $numberOfLines;
                    for ($i = 0; $i < $numOfColors; $i++) {
                        $colors[$i] = [rand(0, 255), rand(0, 255), rand(0, 255)];
                    }
                } else {
                    $numOfColors = count($customColors);
                    for ($i = 0; $i < $numOfColors; $i++) {
                        $colors[$i] = $this->setColor($customColors[$i]);
                    }
                }
                // create array which contains the color of each line as rgb value
                for ($i = 0; $i < $numberOfLines; $i++) {
                    if (array_key_exists($i, $colors)) {
                        $colorList[] = $colors[$i];
                    } else {
                        $colorList[] = $colors[$i % $numOfColors];
                    }
                }

                return $colorList;
            }
            return $colorList;
        }

        /**
         * Add some distortion lines over the captcha image depending on the settings
         * Number of distortion lines (level) and number of random colors can be changed
         * @param GdImage $img
         * @return void
         * @throws WireException
         */
        protected function createLines(GdImage $img): void
        {
            if ($this->getNumberOfLines() > 0) {

                $c = $this->createRGBColorArray();

                for ($line = 0; $line < $this->getNumberOfLines(); ++$line) {

                    imagesetthickness($img, rand(1, 3));
                    imagearc(
                        $img,
                        rand(1, ($this->getWidth() / 2)), // x-coordinate of the center.
                        rand(1, ($this->getHeight() / 2)), // y-coordinate of the center.
                        rand(1, $this->getWidth() * 2), // The arc width.
                        rand(1, $this->getHeight() * 2), // The arc height.
                        rand(1, 300), // The arc start angle, in degrees.
                        rand(1, 300), // The arc end angle, in degrees.
                        imagecolorallocate($img, $c[$line][0], $c[$line][1], $c[$line][2])
                    );
                }
            }
        }

        /**
         * CAPTCHA
         */

        /**
         * Create the reload link object
         * @return Link
         */
        protected function createReloadLink(): Link
        {
            $this->reloadLink->setCSSClass('reloadLinkClass');
            $this->reloadLink->setAttribute('href', '#');
            $this->reloadLink->setAttribute('title', $this->_('Click to load a new captcha'));
            $this->reloadLink->setText($this->_('Reload image'));
            $this->reloadLink->wrap()->setAttribute('class', 'reload-link-wrapper');
            return $this->reloadLink;
        }

        /**
         * Create the captcha image tag object for the captcha image
         * @param string $formID
         * @return Image
         */
        protected function createCaptchaImageTag(string $formID): Image
        {
            $this->captchaImageTag->setCSSClass('captchaClass');
            $this->captchaImageTag->setAttribute('alt', $this->_('Captcha'));
            $this->captchaImageTag->setAttribute('src',
                '/captchaimage.php?formID=' . $formID . '&cat=' . $this->category . '&type=' . $this->type);
            $this->captchaImageTag->wrap()->setAttribute('class', 'image-wrapper');
            return $this->captchaImageTag;
        }
        
        abstract public function createCaptchaImage(string $formID): void;

        abstract public function createCaptchaInputField(string $formID): Inputfields;

    }
