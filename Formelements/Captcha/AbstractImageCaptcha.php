<?php
declare(strict_types=1);

/*
 * Abstract class for creating a captcha using images (no text)
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: AbstractImageCaptcha.php
 * Created: 05.08.2022 
 */

namespace FrontendForms;

use Exception;
use GdImage;
use ProcessWire\WireException;

abstract class AbstractImageCaptcha extends AbstractCaptcha
{

    protected string $imagePath = ''; // the path to the captcha images directory
    protected InputRadioMultiple $captchaInput; // the multi-checkbox input field
    protected string $randomImage = ''; // path to the random image
    protected array $catOptions = []; // array of all captcha categories (car, ship, house,..)

    public string $title = ''; // the title of the image CAPTCHA
    public string $desc = ''; // the description of the image CAPTCHA
    public function __construct()
    {
        parent::__construct();
        $this->category = 'image';
        $this->imagePath = $this->wire('config')->paths->siteModules . 'FrontendForms/captchaimages/';
        $this->captchaInput = new InputRadioMultiple('captcha');
        $this->captchaInput->topLabel->setText($this->_('Please select what you see in the image above.'));
    }

    /**
     * Get the intensity of the blur effect as number between 0 - 10
     * @return int
     */
    protected function getBlurlevel(): int
    {
        if ($this->frontendforms['input_blurlevel'] < 0) {
            return 0;
        } else {
            if ($this->frontendforms['input_blurlevel'] > 10) {
                return 10;
            } else {
                return $this->frontendforms['input_blurlevel'];
            }
        }
    }

    /**
     * Get the intensity of the pixelated effect as a number between 0 - 5
     * @return int
     */
    protected function getPixelatelevel(): int
    {
        if ($this->frontendforms['input_pixelatelevel'] < 0) {
            return 0;
        } else {
            if ($this->frontendforms['input_pixelatelevel'] > 5) {
                return 5;
            } else {
                return $this->frontendforms['input_pixelatelevel'];
            }
        }
    }

    /**
     * Get the value if grayscale effect is disabled or not
     * @return int
     */
    protected function getGrayscale(): int
    {
        return (int)$this->frontendforms['input_grayscale'];
    }

    /**
     * Choose a random image of one of the captcha directories ('car, house,...)
     * @return void
     * @throws WireException
     */
    protected function setRandomImage(): void
    {
        $images = $this->wire('files')->find($this->imagePath, ['recursive' => 2, 'extensions' => ['jpg']]);
        $imgNumber = array_rand($images);
        $this->randomImage = $images[$imgNumber];
    }

    /**
     * Return the path to the random image
     * @return string
     */
    protected function getRandomImage(): string
    {
        return $this->randomImage;
    }

    /**
     * Take a random image and resize it
     * @param string $file
     * @param int $w
     * @param int $h
     * @param bool $crop
     * @return GdImage
     */
    protected function resizeImage(string $file, int $w, int $h, bool $crop = false): GdImage
    {
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        if ($crop) {
            if ($width > $height) {
                $width = ceil($width - ($width * abs($r - $w / $h)));
            } else {
                $height = ceil($height - ($height * abs($r - $w / $h)));
            }
            $newwidth = $w;
            $newheight = $h;
        } else {
            if ($w / $h > $r) {
                $newwidth = $h * $r;
                $newheight = $h;
            } else {
                $newheight = $w / $r;
                $newwidth = $w;
            }
        }

        $src = imagecreatefromjpeg($file);
        $dst = imagecreatetruecolor((int)$newwidth, (int)$newheight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, (int)$newwidth, (int)$newheight, (int)$width, (int)$height);

        return $dst;
    }

    /**
     * Returns the image category name from a path
     * This function takes the path to the image and grabs the last directory name
     * @param string $pathToCaptchaImage
     * @return string
     */
    private function getImageCategory(string $pathToCaptchaImage): string
    {
        $path = pathinfo($pathToCaptchaImage, PATHINFO_DIRNAME);
        return basename($path);
    }

    /**
     * Create the captcha image on the fly depending on the settings
     * @param string $formID
     * @return void
     * @throws WireException
     */
    public function createCaptchaImage(string $formID): void
    {
        $this->setRandomImage(); // set the path to a random image
        $category = $this->getImageCategory($this->getRandomImage());

        // resize the image
        $img = $this->resizeImage($this->getRandomImage(), $this->getWidth(), $this->getHeight());

        // FILTERS

        //1) blur effect
        if ($this->getBlurlevel() !== 0) {
            for ($i = 0; $i < $this->getBlurlevel(); $i++) {
                imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
            }
        }

        //2) pixelate effect
        if ($this->getPixelatelevel() !== 0) {
            imagefilter($img, IMG_FILTER_PIXELATE, $this->getPixelatelevel());
        }

        //3) grayscale effect
        if ($this->getGrayscale()) {
            imagefilter($img, IMG_FILTER_GRAYSCALE);
        }

        // add some distortion lines
        $this->createLines($img);

        // set the captcha solution to the session variable for form validation
        $this->setCaptchaValidValue($category); // Set the category of the random image chosen
        $this->wire('session')->set('captcha_' . $formID, $this->getCaptchaValidValue()); // create the session first

        // output the final image
        header('Content-Type: image/png');
        imagepng($img, null, 0, PNG_NO_FILTER);
        imagedestroy($img);
    }

    /**
     * Create a multiple checkbox list of all captcha categories based on the directories inside the captchaimages folder
     * @return array
     * @throws WireException
     */
    protected function createOptions(): array
    {
        $path_to_img_dir = $this->imagePath;
        $categories = [
            'animal' => $this->_('Animal'),
            'car' => $this->_('Car'),
            'flower' => $this->_('Flower'),
            'house' => $this->_('House'),
            'lake' => $this->_('Lake'),
            'mountain' => $this->_('Mountain'),
            'tree' => $this->_('Tree')
        ];
        $options = [];
        foreach ($categories as $value => $label) {
            if (count($this->wire('files')->find($path_to_img_dir.$value)) !== 0) { // show only directories with content in it
                $options[$value] = $label;
            }
        }
        $this->catOptions = $options;
        return $options;
    }

    /**
     * Create the captcha inputfield for a captcha including image tag and reload link
     * The inputfield for the captcha is an object of type InputText
     * @param string $formID
     * @return InputRadioMultiple
     * @throws WireException
     * @throws Exception
     */
    public function createCaptchaInputField(string $formID): InputRadioMultiple
    {

        // start creating the captcha input field including image and reload link
        $this->captchaInput->alignVertical();
        // Remove or add wrappers depending on settings
        $this->captchaInput->setAttribute('name', $formID . '-captcha');

        $this->captchaInput->useInputWrapper($this->useInputWrapper);
        $this->captchaInput->useFieldWrapper($this->useFieldWrapper);
        $this->captchaInput->getFieldWrapper()->setAttribute('class', 'captcha');
        $this->captchaInput->getInputWrapper()->setAttribute('class', 'captcha');

        $this->captchaInput->setLabel($this->_('CAPTCHA field for SPAM protection'));

        $this->captchaInput->getInputWrapper()->prepend($this->createCaptchaImageTag($formID)
                ->setAttribute('id', $formID . '-captcha-image')
                ->render() . // render captcha image
            $this->createReloadLink()
                ->setAttribute('id', $formID . '-reload-link')
                ->setAttribute('onclick',
                    'reloadCaptcha(\'' . $formID . '-captcha-image\', event); loadCaptchaSolutions(\'' . $formID . '-captcha-inputwrapper\', event)')
                ->render().'<div class="captcha-input-wrapper">'); // render the reload link

        if (!$this->catOptions) { // prevent the same options being added multiple times after reloading the page
            foreach ($this->createOptions() as $value => $label) {
                $this->captchaInput->addOption($label, $value);
            }
        }
        $this->captchaInput->getInputWrapper()->append('</div>');
        return $this->captchaInput;

    }

}
