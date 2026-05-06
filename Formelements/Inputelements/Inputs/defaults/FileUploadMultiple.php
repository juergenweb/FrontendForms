<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Pre-defined file upload field for multiple files
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: MultipleFileUpload.php
 * Created: 19.02.2023
 * Optimized via Claude AI 06.05.26
 */


use Exception;

class FileUploadMultiple extends InputFile
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Upload multiple files'));
    }

    /**
     * Render the file upload input field
     * @return string
     */
    public function ___renderFileUploadMultiple(): string
    {
        return parent::renderInputFile();
    }

}
