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

echo  '<h2>A simple working file upload form for uploading files to a page</h2>';

$form = new \FrontendForms\Form('upload');
$form->setAttribute('enctype', 'multipart/form-data');
$form->setUploadPath($page->id, true);

$file1 = new \FrontendForms\InputFile('fileupload1');
$file1->setLabel('Multiple files upload');
$file1->allowMultiple(true);
$form->add($file1);

$file2 = new \FrontendForms\InputFile('fileupload2');
$file2->setLabel('Single file upload');
$form->add($file2);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if ($form->isValid()) {
    echo '<p>The file(s) has/have been uploaded successfully.</p>';
}

echo $form->render();

$page_files = $files->find($config->paths->assets.'files/'.$page->id);
if($page_files){
    echo '<p>List of page files:</p>';
    echo '<ul>';
    foreach($page_files as $value){
        echo '<li>'.$value.'</li>';
    }
    echo '</ul>';
}
