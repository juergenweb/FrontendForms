<?php
    declare(strict_types=1);

    namespace Processwire;

    /*
     * Holds the array of all question fields data
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: questionFields.php
     * Created: 10.06.2024
     */

    $this->questionFields = [
        'ff_answers' => [
            'fieldtype' => 'FieldtypeTextarea',
            'label' => $this->_("Accepted answers"),
            'description' => $this->_("Add all accepted answers (each on a new line)."),
            'required' => 1,
            'textformatters' => ["TextformatterEntities"],
            'stripTags' => 1
        ],
        'ff_errormsg' => [
            'fieldtype' => 'FieldtypeText',
            'label' => $this->_("Custom error message"),
            'description' => $this->_("Set a custom error message if you want."),
            'notes' => $this->_("This will overwrite the default error message if the CAPTCHA answer was wrong.")
        ],
        'ff_successmsg' => [
            'fieldtype' => 'FieldtypeText',
            'label' => $this->_("Success message"),
            'description' => $this->_("Set a custom success message if you want."),
            'notes' => $this->_("This message will be shown, if the answer of the CAPTCHA was correct, but there were other errors.")
        ],
        'ff_placeholder' => [
            'fieldtype' => 'FieldtypeText',
            'label' => $this->_("Placeholder text"),
            'description' => $this->_("Set a placeholder text if you want.")
        ],
        'ff_notes' => [
            'fieldtype' => 'FieldtypeText',
            'label' => $this->_("Notes text"),
            'description' => $this->_("Set a notes text if you want."),
            'notes' => $this->_("The notes text will be displayed below the input field.")
        ],
        'ff_description' => [
            'fieldtype' => 'FieldtypeText',
            'label' => $this->_("Description text"),
            'description' => $this->_("Set a description text if you want."),
            'notes' => $this->_("The description text will be displayed below or above the label or below the input field.")
        ],
        'ff_descposition' => [
            'fieldtype' => 'FieldtypeOptions',
            'label' => $this->_("Description position"),
            'description' => $this->_("You can change the position of the description text if you want."),
            'notes' => $this->_("This will overwrite the default description position."),
            'defaultValue' => '1',
            'required' => 1
        ]
    ];
