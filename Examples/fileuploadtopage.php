<?php
declare(strict_types=1);

namespace ProcessWire;
/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: fileuploadtopage.php
 * Created: 13.02.2023
 */

/*
 * Demonstration of a simple file upload form
 * You can copy this code to a template to show the form
 * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
 * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
 */

$content =  '<h2>A simple working file upload form for uploading files to a page</h2>';

$folder = $page->id; // set page id as the folder name

$content .= '<p>Files will be uploaded to site/assets/files/'.$folder.' by default, but you can choose 
another folder by using the setUploadPath() method. But be aware that the folder must be writable.</p>';

$form = new \FrontendForms\Form('upload');

$file1 = new \FrontendForms\InputFile('fileupload1');
$file1->setLabel('Multiple files upload');
$file1->setMultiple(true); // this method makes converts the upload field to be a multi-upload field
$form->add($file1);

$file2 = new \FrontendForms\InputFile('fileupload2');
$file2->setLabel('Single file upload');
$form->add($file2);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if ($form->isValid()) {
    $content .= '<p>The file(s) has/have been uploaded successfully.</p>';
}

$content .= $form->render();

$page_files = $files->find($config->paths->assets.'files/'.$page->id);
if($page_files){
    $content .= '<p>List of page files inside the folder '.$folder.':</p>';
    $content .= '<ul>';
    foreach($page_files as $value){
        $content .= '<li>'.$value.'</li>';
    }
    $content .= '</ul>';
}

echo $content;
