<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input text element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputText.php
 * Created: 03.07.2022
 */

use Exception;

class InputText extends Input
{
    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputText(): string
    {
        // create HTML5 minlength attribute depending on validator settings
        if(array_key_exists('lengthMin',$this->notes_array)){
            $this->setAttribute('minlength', (string)$this->notes_array['lengthMin']);
        }

        // create HTML5 max attribute depending on validator settings
        if(array_key_exists('lengthMax',$this->notes_array)){
            $this->setAttribute('maxlength', (string)$this->notes_array['lengthMax']);
        }

        // do not add these patterns on input tel
        if(get_class($this) != 'FrontendForms\InputTel') {

            // create HTML5 pattern attribute for alphabetical characters only depending on validator settings
            if(array_key_exists('alpha',$this->notes_array)){
                $this->setAttribute('pattern', '[a-zA-Z]+');
            }

            // create HTML5 pattern attribute for alphanumeric characters only depending on validator settings
            if(array_key_exists('alphaNum',$this->notes_array)){
                $this->setAttribute('pattern', '[A-Za-z0-9]+');
            }

            // create HTML5 pattern attribute for ascii characters only depending on validator settings
            if(array_key_exists('ascii',$this->notes_array)){
                $this->setAttribute('pattern', '[\x00-\x7F]+');
            }

            // create HTML5 pattern attribute for slug characters only depending on validator settings
            if(array_key_exists('slug',$this->notes_array)){
                $this->setAttribute('pattern', '[A-Za-z0-9-_]+');
            }

        }

        return $this->renderInput();
    }

}
