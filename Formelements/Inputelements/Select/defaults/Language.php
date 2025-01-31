<?php
declare(strict_types=1);

/*
 * Class for creating a language select element
 * Contains all languages installed on the site
 * Outputs the language names always in the current user language
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: Language.php
 * Created: 06.03.2023 
 */


namespace FrontendForms;

use ProcessWire\LanguagesPageFieldValue;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Language extends Select
{

    protected int|null $fixed_lang_id = null; // set fixed language as default independent of site language
    protected int $user_lang_id = 1010; // the current site language

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id)
    {
        parent::__construct($id);

        // get the current user language
        $this->user_lang_id = $this->user->language->id;
        $this->setLabel($this->_('Language'));

        // create all language options
        $languageIDs = [];
        foreach ($this->wire('languages') as $lang) {
            $languageIDs[] = $lang->id;
            if($lang->title instanceof LanguagesPageFieldValue) {
                $title = $lang->title->getLanguageValue($this->user_lang_id );
            } else {
                $title = $lang->title;
            }
            $this->addOption($title, (string)$lang->id);
        }

        $this->setRule('required')->setCustomFieldName($this->_('Language'));
        $this->setRule('integer');
        $this->setRule('In', $languageIDs);

    }

    /**
     * Set a fixed language to be selected
     * @param int $id
     * @return $this
     */
    public function setFixedLanguageID(int $id):self
    {
        $this->fixed_lang_id = $id;
        return $this;
    }

    /**
     * Render the language select input field
     * Render it only if it is a multi-language site, otherwise output only an empty string
     * @return string
     * @throws WireException
     */
    public function renderLanguage(): string
    {
        if(count($this->wire('languages')) > 1){

            $value = (is_null($this->fixed_lang_id)) ? $this->user_lang_id : $this->fixed_lang_id;
            $this->setDefaultValue($value);
            return parent::renderSelect();
        }
        return '';
    }
}
