<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Pre-defined file upload field for a single file
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: FileUpload.php
 * Created: 15.02.2023
 * Optimized via Claude AI 06.05.26
 */

use Exception;

class FileUploadSingle extends InputFile
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setMultiple(false);
        $this->setLabel($this->_('Upload single file'));
    }

    /**
     * Render the file upload input field
     * @return string
     */
    public function ___renderFileUploadSingle(): string
    {
        return parent::renderInputFile();
    }

}
