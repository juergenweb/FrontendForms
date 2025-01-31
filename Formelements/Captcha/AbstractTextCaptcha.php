<?php
declare(strict_types=1);

/*
 * Abstract class for creating captcha with text (no images)
 * Extends from the general AbstractCaptcha class
 * Will be used for random text and math captcha
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: AbstractTextCaptcha.php
 * Created: 05.08.2022 
 */


namespace FrontendForms;

use ErrorException;
use Exception;
use GdImage;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

abstract class AbstractTextCaptcha extends AbstractCaptcha
{

    protected string $captchaContent = ''; // the content of the captcha (random string or calculation) as shown in the image
    protected string $pathToFonts = ''; // the path to the ttf font files directory
    public string $title = ''; // the name for the captcha in the backend select
    public string $desc = ''; // the description of the captcha in the backend select

    /**
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->pathToFonts = $this->wire('config')->paths->siteModules . 'FrontendForms/Formelements/Captcha/fonts/'; // path to the font files
        $this->category = 'text';
    }

    /**
     * Get the type of the background color (custom or random)
     * @return string
     * @throws WireException
     */
    protected function getBackgroundType(): string
    {
        if (in_array($this->frontendforms['input_bgcolorchooser'], ['random', 'custom'])) {
            return $this->frontendforms['input_bgcolorchooser'];
        } else {
            // return default value from module configuration instead
            return $this->wire('modules')->getModuleConfigData('FrontendForms')['input_bgcolorchooser'];
        }
    }


    /**
     * Get the colors of the background as an array
     * As fallback value #ddd will be used as output
     * @return array
     * @throws Exception
     */
    protected function getBackgroundColor(): array
    {
        return AbstractCaptcha::linebreaksValuesToArray($this->frontendforms['input_bgCustomColors'], '#ddd');
    }

    /**
     * Set the number of colors, which should be used for the background
     * The higher the number, the more colorful the background
     * @param int $number
     * @return $this
     */
    protected function setNumberOfColors(int $number): self
    {
        $this->frontendforms['input_bgnumberOfColors'] = $number;
        return $this;
    }

    /**
     * Get the number of colors that should be used for the background
     * @return int
     */
    protected function getNumberOfColors(): int
    {
        return $this->frontendforms['input_bgnumberOfColors'];
    }

    /**
     * Get the text color
     * @return array
     * @throws Exception
     */
    protected function getTextColor(): array
    {
        return $this->setColor($this->frontendforms['input_captchaTextColor']);
    }

    /**
     * Get the font size of the captcha text
     * @return int
     */
    protected function getFontSize(): int
    {
        return (int)$this->frontendforms['input_captchaFontsize'];
    }

    /**
     * Get the font family for the captcha text
     * @param bool $showPath
     * @return string
     * @throws ErrorException
     * @throws WireException
     */
    protected function getFontFamily(bool $showPath = false): string
    {
        if ($this->wire('files')->exists($this->frontendforms['input_captchaFontFamily'])) {
            if (pathinfo($this->frontendforms['input_captchaFontFamily'], PATHINFO_EXTENSION) == 'ttf') {
                if ($showPath) {
                    return pathinfo($this->frontendforms['input_captchaFontFamily'], PATHINFO_BASENAME);
                }
                return $this->frontendforms['input_captchaFontFamily'];
            }
            throw new ErrorException($this->_('This file is not a TrueType font file.'));
        }
        throw new ErrorException($this->_('This file does not exist under the specified path'));
    }

    /**
     * Generate array of text coordinates [x, y] for centering text inside the captcha image
     * @return float[]|int[]
     * @throws ErrorException
     * @throws WireException
     */
    protected function generateTextPosition(): array
    {
        $box = imagettfbbox($this->getFontSize(), 0, $this->getFontFamily(), $this->getCaptchaContent());
        $textWidth = abs($box[2] - $box[0]); // distance bottom right to bottom left
        $textHeight = abs($box[7] - $box[1]); // distance top left to bottom left
        // return coordinates for the bottom left corner of the text
        return [
            'x' => (int)(($this->getWidth() - $textWidth) / 2),
            'y' => (int)(($this->getHeight() - $textHeight) / 2) + $this->getFontSize()
        ];
    }

    /**
     * Set the content of the captcha
     * @param string $content
     * @return $this
     */
    protected function setCaptchaContent(string $content): self
    {
        $this->captchaContent = $content;
        return $this;
    }

    /**
     * Get the content of the captcha (random string or calculation)
     * @return string
     */
    protected function getCaptchaContent(): string
    {
        return $this->captchaContent;
    }

    /**
     * Add the text for the captcha to the image
     * @param GdImage $img
     * @param int $color
     * @param string $formID
     * @return void
     * @throws WireException
     * @throws WirePermissionException
     * @throws ErrorException
     */
    protected function createText(GdImage $img, int $color, string $formID): void
    {
        $content = $this->getCaptchaContent();
        $this->setCaptchaValidValue($content); // create and set the real valid captcha value depending on which type of captcha was chosen
        $this->wire('session')->set('captcha_' . $formID, $this->getCaptchaValidValue()); // create the session first
        $coordinates = $this->generateTextPosition(); // calculate the position of the text next

        if ($this->frontendforms['input_charactersOffLine']) {
            // text off the line
            $captcha_string = str_split($content);
            $initial = 15;
            $randomHeight = ($this->getHeight() - $this->getFontSize());
            $heightInterval = [(int)($randomHeight * 1.0), (int)($randomHeight * 1.1)];

            for ($i = 0; $i < count($captcha_string); $i++) {
                $letter_space = ($this->getWidth() - ($initial * 2)) / (count($captcha_string));
                imagettftext($img, $this->getFontSize(), rand(-15, 15), (int)($initial + $i * $letter_space),
                    rand($heightInterval[0], $heightInterval[1]), $color, $this->getFontFamily(), $captcha_string[$i]);
            }
        } else {
            imagettftext($img, $this->getFontSize(), 0, $coordinates['x'], $coordinates['y'], $color,
                $this->getFontFamily(), $content);
        }
    }

    /**
     * Create the captcha image on the fly depending on the settings
     * @param string $formID
     * @return void
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function createCaptchaImage(string $formID): void
    {

        // create image object
        $img = imagecreate($this->getWidth(), $this->getHeight());

        // fill the background with color(s)
        $this->createBackground($img);

        // set the text inside the image
        $textcolor = $this->getTextColor();
        $tc = imagecolorallocate($img, $textcolor[0], $textcolor[1], $textcolor[2]);
        $this->createText($img, $tc, $formID);

        // add some distortion lines
        $this->createLines($img);

        // output the final image
        header("Content-type: image/png");
        imagepng($img, null, 0, PNG_NO_FILTER);
        imagedestroy($img);

    }

    /**
     * Create the captcha inputfield for a captcha including image tag and reload link
     * The inputfield for the captcha is an object of type InputText
     * @param string $formID
     * @return InputText
     */
    public function createCaptchaInputField(string $formID): InputText
    {
        // start creating the captcha input field including image and reload link
        $captchaInput = new InputText('captcha');
        // Remove or add wrappers depending on settings
        $captchaInput->setAttribute('name', $formID . '-captcha');
        $captchaInput->useInputWrapper($this->useInputWrapper);
        $captchaInput->useFieldWrapper($this->useFieldWrapper);
        $captchaInput->getFieldWrapper()->setAttribute('class', 'captcha');
        $captchaInput->getInputWrapper()->setAttribute('class', 'captcha');
        $captchaInput->setLabel($this->_('Captcha field for security'));
        $captchaInput->prepend($this->createCaptchaImageTag($formID)
                ->setAttribute('id', $formID . '-captcha-image')
                ->render() . // render captcha image
            $this->createReloadLink()
                ->setAttribute('id', $formID . '-reload-link')
                ->setAttribute('onclick', 'reloadCaptcha(\'' . $formID . '-captcha-image\', event)')
                ->render().'<div class="captcha-input-wrapper">'); // render the reload link
        $captchaInput->append('</div>');
        return $captchaInput;
    }

    /**
     * Creates an array containing arrays of rgb values as integers
     * @param int $number
     * @return array
     */
    protected function createRandomColorsArray(int $number): array
    {
        $colors = [];

        for ($i = 1; $i <= $number; $i++) {
            for ($i = 0; $i < $number; $i++) {
                $colors[$i] = [rand(0, 255), rand(0, 255), rand(0, 255)];
            }
        }
        return $colors;
    }

    /**
     * @throws WireException
     * @throws Exception
     */
    protected function createBackground(GdImage $img): void
    {
        imageantialias($img, true);
        $numberOfRectangles = (int)($this->getHeight() / 5); // calculate number of rectangles depending on the height
        if ($this->getBackgroundType() == 'custom') {
            // custom colors
            $colors = [];
            $numberOfRectangles = count($this->getBackgroundColor()) - 1;
            foreach ($this->getBackgroundColor() as $color) {
                $colors[] = AbstractCaptcha::hex2rgb($color);
            }
        } else {
            // random colors

            if ($this->getNumberOfColors() == 0) { // unlimited
                $this->setNumberOfColors($numberOfRectangles);
            }
            $colors = $this->createRandomColorsArray($this->getNumberOfColors());
        }

        $this->wire('session')->set('color', $this->getNumberOfColors());
        $bgc = imagecolorallocate($img, $colors[0][0], $colors[0][1], $colors[0][2]);
        imagefill($img, 0, 0, $bgc);
        unset($colors[0]);
        $colors = array_values($colors); // re-sort the array

        if ($colors) { // if not single color
            $colorList = [];
            // create array which contains the color of each rectangle as rgb value
            for ($i = 1; $i <= $numberOfRectangles + 1; $i++) {
                if (array_key_exists($i, $colors)) {
                    $colorList[$i] = $colors[$i];
                } else {
                    $colorList[$i] = $colors[$i % count($colors)];
                }
            }
            $colorList = array_filter($colorList);
            $colorList = array_values(array_filter($colorList)); // re-sort the array new
            $this->wire('session')->set('color', $colorList);
            if ($colorList) {
                // multicolor
                if ($numberOfRectangles > 0) {
                    for ($i = 0; $i <= $numberOfRectangles; $i++) {
                        imagesetthickness($img, rand(2, 10));
                        $rect_color = imagecolorallocate($img, $colorList[$i][0], $colorList[$i][1], $colorList[$i][2]);
                        imagerectangle($img, rand(-10, $this->getHeight() + 10), rand(-10, 0),
                            rand(-10, $this->getWidth() + 10), rand(-10, $this->getWidth() + 10), $rect_color);
                    }
                }
            }
        }
    }

}
