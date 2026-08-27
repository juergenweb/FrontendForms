<?php
    declare(strict_types=1);

    namespace ProcessWire;
    /*
     * File description
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: ajaxform.php
     * Created: 16.10.2022
     */

    /*
     * Demonstration of a form which will be submitted using Ajax
     * You can copy this code to a template to show the form
     * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
     * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
     */

    $content = '<h2>A simple form using Ajax for submission</h2>';

    $form = new \FrontendForms\Form('ajax');
    $form->setSubmitWithAjax(); // enable Ajax support if it has not been enabled inside the global configuration
    $form->setRedirectUrlAfterAjax($page->url . '#target'); // optional: force a redirect after the form has been validated successful
    /*
    Only to mention: this redirect makes not really a sense and is only for demonstration purposes
    A real life example could be using a redirect after login to redirect to a profile page or whatever.
    The internal anchor (#target) is also only for demo purposes to show the usage of an anchor inside a redirect
    */

    $name = new \FrontendForms\InputText('name');
    $name->setLabel('Name');
    $name->setRule('required')->setCustomFieldName('The name');
    $form->add($name);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want

    }

    $content .= $form->render();
    $content .= '<div id="target">This is the target container for the redirect after the form has been validated successfully.</div>';
    echo $content;

