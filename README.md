# FrontendForms
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![ProcessWire 3](https://img.shields.io/badge/ProcessWire-3.x-orange.svg)](https://github.com/processwire/processwire)

A module for ProcessWire to create and validate forms on the frontend easily using the [Valitron](https://github.com/vlucas/valitron) library.


## Highlights
1. Simple form creation
2. 60+ validation types (rules) to validate form fields
3. The ability to create your own validation rules
4. Support for UiKit 3, Pico 2 and Bootstrap 5 CSS framework
5. SPAM protection and security features
6. Highly customizable
7. Multi-language
8. Option to send emails using HTML email templates within the WireMail class by using custom methods and properties added to the WireMail class (supports also using these custom methods and properties via PostmarkApp, WireMailPHPMailer and WireMailSMTP)
9. Ajax support for form submission
10. Contains an optional additonal module (FrontendForms Manager) for installation to provide an userfriendly UI for the SIMPLE QUESTION CAPTCHA
11. Support for inputfield dependencies (show/hide fields depending on values of other fields)
12. Support for using forms inside CKEditor fields via placeholders
13. Support for [RockLanguage](https://processwire.com/modules/rock-language/)

## Requirements
* PHP>=8.0.0
* ProcessWire>=3.0.181
* GD-Library for CAPTCHA image creation

This module will work without GD-Library too, but you will not be able to use CAPTCHA in this case.

## Live examples

You can see FrontendForms in action on the following sites:

* [schulfreund.at](https://www.schulfreund.at/kontakt/)
* [fugamo.de](https://fugamo.de/kontakt/)
* [surfdacha.com](https://surfdacha.com/en/booking/)

## Table of contents
* [Installation and Quick-start guide](#installation-and-quick-start-guide)
* [Support for CSS frameworks](#support-for-css-frameworks)
* [Which input types are supported?](#which-input-types-are-supported)
* [SPAM protection](#spam-protection-and-security-features)
* [Prevent double form submission](#prevent-double-form-submission)
* [General methods](#general-methods)
* [Form and its methods](#form-methods)
* [Input fields and their methods](#input-field-methods)
* [Additional Markup](#additional-markup)
* [Form validation](#form-validation)
* [Create your own custom validation rules](#create-your-own-custom-validation-rules)
* [Customization of validation](#customization-of-validation)
* [Other form elements](#other-form-elements)
* [Default fields](#default-fields)
* [File uploads](#file-uploads)
* [Hooking](#hooking)
* [Multi-language](#multi-language)
* [HTML email templates](#email-templates)
* [Use forms in iframe](#use-forms-in-iframe)
* [Extra module: FrontendForms Manager](#custom-module-frontendformsmanager)
* [Inputfield dependencies](#inputfield-dependencies)
* [Adding forms via placeholders to CKEditor fields](#adding-forms-via-placeholders-to-ckeditor-fields)

## Installation and Quick-start guide
The most simple way is to install it via the ProcessWire module manager in the administration area, but you can also install it manually following the steps afterwards:

1. Download and extract FrontendForms and put the folder inside site/modules. Be aware that the folder name must be
   FrontendForms and not FrontendForms-main or FrontendForms-master. GitHub adds this appendix by default. So be aware to remove it before you put the folder inside the module folder.
2. Login to your admin area and refresh all modules.
3. Find this module and install it.
4. After you have installed the module you can change some configuration settings if needed, but the module works out of the box.

### Create the first form

Before you start creating your own forms, I recommend you to start with a ready to use example. Copy the following code and paste it inside a template of your choice, but be aware of namespaces!!
If you are using a namespace on the top of your template file, you have to adapt the instantiation of the class by using the FrontendForms + leading backslash in front of the namespace.
This module runs in its own namespace called *FrontendForms*.

Take a look at the following example:

```php

// if you are not using a namespace at the top of your template file, you only have to call the namespace without leading backslash at the beginning
$form = new FrontendForms\Form('myForm'); // usage with custom ID inside the constructor and namespace in front of the class name.

// but if you are using a template with a namespace you have to add the leading backslash to the FrontendForms namespace
$form = new \FrontendForms\Form('myForm'); // take a look at the leading '\' in front of the namespace
// you have to do it on every class instantiation (fe input field, select field,..) not only on the Form class as in
// the example above
```

Tipp: Instead of writing the namespace in front of every new instantiated form object, you can also use PHPs 'use' function on top of your file - it is up to you.

```php
$form = new \FrontendForms\Form('myForm');

$gender = new \FrontendForms\Select('gender');
$gender->setLabel('Gender');
$gender->addOption('Mister', 'Mister');
$gender->addOption('Miss', 'Miss');
$form->add($gender);

$surname = new \FrontendForms\InputText('surname');
$surname->setLabel('Surname');
$surname->setRule('required');
$form->add($surname);

$name = new \FrontendForms\InputText('lastname');
$name->setLabel('Last Name');
$name->setRule('required');
$form->add($name);

$email = new \FrontendForms\InputText('email');
$email->setLabel('E-Mail');
$email->setRule('required');
$email->setRule('email');
$form->add($email);

$subject = new \FrontendForms\InputText('subject');
$subject->setLabel('Subject');
$subject->setRule('required');
$form->add($subject);

$message = new \FrontendForms\Textarea('message');
$message->setLabel('Message');
$message->setRule('required');
$form->add($message);

$privacy = new \FrontendForms\InputCheckbox('privacy');
$privacy->setLabel('I accept the privacy policy');
$privacy->setRule('required')->setCustomMessage('You have to accept our privacy policy');
$form->add($privacy);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if($form->isValid()){
  print_r($form->getValues());
  // do what you want
}

// render the form
echo $form->render();

```
### Short Explanation step by step
* First of all you have to create a new form object. Inside the constructor you have to add the id of the form.
* After that you can create each form field of the form. Each form field must have a name attribute inside the constructor (required).
* You can set various properties to each form field (setLabel(), setNotes(), setDescription();setRule(), setSanitizer(),...)
* Use the add() method to add each field to the form object.
* The isValid() method returns true or false, and you can use it to send fe the values via email or save them to the database, to login a user,....you get the idea. The validation and sanitization of the form values happens inside this method.
* The render method outputs the markup of the form.

> ⚠️ I highly recommend you to study the examples inside the ['examples'](https://github.com/juergenweb/FrontendForms/tree/main/Examples) folder. There you will find a lot of different use cases. Some examples are simple, others are more complex. There are also examples including file upload.

Now you are ready to use the module inside your project!

## Support for CSS frameworks

This module comes with native support for Bootstrap 5, UIKit 3 and Pico CSS framework at the time of writing. Selecting one of these frameworks in the module configuration changes the classes being used on the frontend inside the forms.

If you want to use your own classes for example of a framework that is not supported by default, you have to add each class in the backend inside the tab "Own CSS classes" This works fine, but has one big disadvantage: You can only change the classnames for the given ProcessWire installation. If you install a new ProcessWire site and you want to use the same classes again, you have to enter them once more.

To make life a little bit easier, you can add your own json file including all the classes inside a new folder called "frameworks". You will find this folder under site/assets/files/frameworks. This folder will not be overwritten during an update.
If you want to store your json file on another location (eg. site/templates/FrontendForms/frameworks), you have to enter the path inside the backend configuration. There is a text input field under the framework select, where you can enter the path to your custom directory.

### How does it work

To create your own json files with your own classes, please copy an existing json file (eg. of the UIKit framework) first. You will find all existing files inside the folder "CSSClasses". It does not matter which of them you choose as your skeleton file for your own classes.

Rename the file with a different name (eg myCSS.json) than the existing ones and add it inside the folder "frameworks" (site/assets/files/frameworks). Now you have to overwrite the classes inside this file with your own classnames.

```json
"alertClass": "my-alert" // eg alertClass will be overwritten from alert to my alert
```
Go to the module configuration in the backend and select your own CSS as the framework that should be used. You will find this configuration setting under "Markup and styling for forms -> Setting for markup and styling -> Output rendering".

That is all and now all classes on the frontend will be replaced by your own classes. 

The big advantage is that you can use this file on another ProcessWire installation in the same way, without the need to write it once more.

**Important note!!**

Please keep in mind that only the classnames will change in this case. This works for most cases, but if you try to add classes from a CSS framework that uses a different markup for forms too, you will probably get not the desired output for your forms. In this case you can write a request on Github for adding support to this framework and probably I will add it ;-)

## Which input types are supported?
You can find examples of all supported input types inside the 'examples' folder in the inputTypes.php

* Inputs ('text, color, date, datetime, email, file, hidden, month, number, password, range, search, tel, time, url, week)
* Radio button (single)
* Radio buttons (multiple)
* Checkbox (single)
* Checkbox (multiple)
* Select (single)
* Select (multiple)
* Textarea
* Datalist

In the table afterwards you will find information about the sanitizers and validation rules used on each input field. If you create a new instance of an input field, please note that these validators and validation rules are added by default, but you have always the possibility to add or remove sanitizers and valdiation rules depending on your preferences.



| Name (Class)  | Sanitizers added by default | Validation rules added by default |
| ------------- | ------------- | ------------- |
| InputText  | text  | -  |
| InputColor  | text  | -  |
| InputDate  | text  | date  |
| InputDateTime  | text  | date  |
| InputEmail  | text  | email, emailDNS  |
| InputFile  | arrayVal  | noErrorOnUpload, phpIniFilesize  |
| InputHidden  | text  | -  |
| InputMonth  | text  | month  |
| InputNumber  | text  | numeric  |
| InputPassword  | text  | meetsPasswordConditions  |
| InputRange  | text  | numeric  |
| InputSearch  | text  | -  |
| InputTel  | text  | -  |
| InputTime  | text  | time  |
| InputUrl  | text  | url, urlActive  |
| InputWeek  | text  | week  |
| InputRadio  | text  | -  |
| InputRadioMultiple  | text  | -  |
| InputCheckbox  | text  | -  |
| InputCheckboxMultiple  | arrayVal  | -  |
| InputSelect  | text  | -  |
| InputSelectMultiple  | arrayVal  | -  |
| Textarea  | textarea  | -  |
| Datalist  | text  | -  |

## SPAM protection and security features

### Measure 1: Set max number of invalid attempts
You can set a number of max attempts for submitting the form successfully inside the module configuration or by adding the [setMaxAttempts()](#setmaxattempts)
method to your form object.
If the number of unsuccessful attempts is higher than the allowed number, the form submission will be blocked.
It is only a soft block by using a session. The vistor will be prompted to close the browser to remove the session and to re-open the page again. If the session is active, the form will not be displayed on the page.
Can be disabled by setting the value to zero.

### Measure 2: Time measurement
You can set a global min and max time for submit a form inside the module configuration, but you can set the them also manually on per form base. In this case you only have to add the [setMinTime() and/or setMaxTime()](#setmintime-setmaxtime) method(s) to your form object. Setting the value to zero disables this feature.
If a visitor or a SPAM bot submits the form outside of this time range, the form will not be submitted.
By the way, SPAM bots tend to fill out forms very quickly or analyse the forms very long and submit them after a long while.
After every submission the time will be calculated new.

**Good to know, but you do not have to do something special**

The value entered for the min time refers to filling out all empty required fields of a form. If some fields contain a value after submission, the time will be
reduced for filling out the form, because there are less fields left. So checking the min time takes care about how manyfields are filled out at the time of submission. This leads to that, that the min time will be reduced, if there a less fields left. So do not be surprised, if the the min time changes during multiple submission attempts.

### Measure 3: Honeypot field
A honeypot field, which changes the position on every page load in random order, will be added automatically by default. If you do not want to include the honeypot field you need to add the [useHoneypot(false)](#usehoneypot) method to you form object (not recommended).
Info: A honeypot field is a field which is hidden to the visitor, but a SPAM bot can read it and if this field will be filled out it will be detected as spam.
Please note that the honeypot field will be hidden via the frontendforms.css file.

```css
.seca {
   opacity: 0;
   position: absolute;
   top: 0;
   left: 0;
   height: 0;
   width: 0;
   z-index: -1;
}
```
You can remove the embedding of the file inside the module configuration, and you can embed this code in your own CSS file if you want.

### Measure 4: IP-Blacklist
Add IP-addresses to a blacklist to prevent them accessing your forms. If the visitor's IP is on this list, an alert box will be displayed,
which informs the visitor, that his IP is on the blacklist. The form itself will not be displayed in this case.

#### Statistic section of blocked visitors to identify spammer
In addition to the IP-banning blacklist, a statistic section which informs you about visitors that have been blocked, is part
of the anti-spam measures.
A visitor will be blocked if he needs, for example, too many attempts to send the form (depending on your settings).
In this section you can get more information about this visitor, and you have 2 buttons: add the visitor to or remove him from the IP blacklist.


**Important note:**

The statistic section works only if you have "logging IP of visitors" enabled inside the security measure 1 (max attempst). If not, than no failde attempt will be logged and therefore no data will be available.

The reason for this is that the storage of IP addresses concerns data protection and should no be automatically, because you have to inform the user before.

If you have not enabled IP logging, you will be informed that no data is available for display.

### Measure 5: CAPTCHA
This module offers various types of a CAPTCHA, that can be used. BTW: CAPTCHA should be used only if the other traps failed, and you get a lot of SPAM over your forms.
Most visitors do not like CAPTCHA, but it is up to you whether to use them or not.
You can make all CAPTCHA settings inside the module configuration. The only thing you can do manually is to disable the CAPTCHA on per form base by using the [disableCaptcha()](#disablecaptcha) method.

At the moment, following CAPTCHA types will be provided:

#### Image CAPTCHA
The image CAPTCHA shows an image and the visitor has to answer which category fits to the image. The following categories
exist at the moment: trees, houses, lakes, flowers, animals, mountains, ships and cars.
You can also manipulate the images by using various filters (can be set in the configuration) to make them more difficult to be identified by spambots.

#### Random string CAPTCHA
A random string will be provided inside an image and the visitor has to write this string into the input field below.

#### Even string CAPTCHA
This is almost the same as the random string CAPTCHA with the only difference, that the visitor has to enter every second
character (even character) and not the whole string.

#### Reverse string CAPTCHA
This is also almost the same as the random string CAPTCHA with the only difference, that the visitor has to enter the characters
from right to left (reverse order) instead of left to right.

#### Math CAPTCHA
The visitor has to solve a simple calculation.

In the backend, there are a lot of configuration settings to adapt the CAPTCHA to your needs or to adapt it to your project
design. The settings are self explaining, so I do not want to go into detail.
The configuration is global and cannot be changed on per form base.

#### Simple Question CAPTCHA

This CAPTCHA contains a simple question, that the user has to answer as free text and not as pre-defined answers. You can define the question and the accepted answers by yourself in the backend, but this can be overwritten on each form.
This CAPTCHA does not use a session to work, so it can be used in forms which will be loaded on another domain inside an iframe.

Example for such a CAPTCHA: 

Question: What color has a green apple?

Possible answers:
* green
* it is green
* green color

This CAPTCHA can be used if you want to make a CAPTCHA more personally by asking something about the company or a person.

#### Slider CAPTCHA

This captcha uses a puzzle piece that has to be solved by pulling the slider into the correct position. It will be validated on the server too. 

### Measure 6: Password blacklist
If you are dealing with user login/registration on your site, there is always a risk, that clients use unsafe passwords
and this could be a serious security issue for an account to be hacked.
For this reason, you have the opportunity to create a blacklist of forbidden passwords in the module configuration. To make it much more simple
for you, it uses passwords from the [top 100 most common passwords](https://github.com/danielmiessler/SecLists/blob/master/Passwords/Common-Credentials/10-million-password-list-top-100.txt) list from GitHub, but you can also add your own passwords.

**Automatic filtering of the blacklist according to your password settings:**

The module takes care about your password requirement settings and does not add all 100 passwords of the top list by default to the
blacklist. This would be an overhead.
Therefore only passwords that fulfill your password requirements will be added to the blacklist - all others will be ignored to keep the list as short as possible (better performance).

Example for better understanding:

Your password requirements as set in the field configuration of the field "pass" are set to "letter" and "number", which
means each password must consists of letters and numbers (other types like symbols are not required).
Passwords that does not fulfill this minimum requirements will be filtered out by the "meetsPasswordConditions" validator
before, which will be added by default to every password field.
So there is no need to add these passwords to the blacklist, because they do not fulfill the requirements at all.

On the module configuration page you will find a very detailed description how the blacklist works.

**Automatic update of the blacklist:**

The top 100 password list will be checked once a month on GitHub, if the file has been modified. Once a month is enough
and GitHub allows only a certain amount of requests per day if you are not using their API. Otherwise, you will get a 403 error (too many requests).
So, checking once a month should be enough and will prevent a 403 error from occurring.
If something has been changed on the list, it will be downloaded and added to the blacklist automatically.
So you do not have to take care about it and the list will be always up-to-date.

### Measure 7: HTML 5 browser validation
If you want to make a frontend validation before the the server-side validation, you can enable HTML 5 browser validation in the module configuration.
This will take HTML 5 attributes (fe. min or required attribute) and validates it by the browser before the form will be submitted to the server.
The browser attributes will be added/removed automatically by adding/removing a validation rule to a field.

Example:

You add the validation rule for checking that the user enters only alphanumeric values inside a text input:

```php
$alphanum = new \FrontendForms\InputText('alphanum');
$alphanum->setLabel('Validator alphaNum');
$alphanum->setRule('alphanum'); // this is the validation rule
$alphanum->setDescription('Validator to check if string contains only alphabetical and numeric characters');
$alphanum->setNotes('Valid value: abc123, invalid value: abc123$');
$form->add($alphanum);
```

As you can see, the validation rules *setRule('alphanum')* was added to the field.

Now let us take a look a the source code of the field created:

```php
<input id="validators-alphanum" name="validators-alphanum" type="text" class="input" pattern="[a-zA-Z0-9]+" title="Validator alphaNum should only contain letters and numbers">
```

As you can see, a pattern with a regex to match only letters and numbers an a custom error message via title attribute will be added automatically only by adding this validation rule.

On the opposite, if you remove a validation rule with *removeRule('alphanum')*, the additional attributes will be removed too.

I have not added a browser validation pattern to every validation rule, because this is not possible, but on approx. 80%.

It is always recommended to take a look a look at the source code of a field to see, what attributes were added. If you are missing one, you can always add it manually to each field.

If you have an idea for an additional regex, let me know :-)

Only to mention: not all browsers support each attribute and the design of the validation messages can differ from browser to browser.


## Prevent double form submission
There is also a session active which prevents double form submission after successful validation.
It compares the session value with the value of a hidden field. If the values are different, it is an indication that
the form would be submitted twice. In this case the submission will be stopped before it takes place, and you will
be redirected to the form page itself.
The double-submission check can be set manually (enable/disable) if necessary by using the [useDoubleFormSubmissionCheck()](#usedoubleformsubmissioncheck) method.


## General methods
General methods are methods that can be used on each object: form, input field, label, description, notes, wrappers, fieldset,...

| Method name  | Use case | 
| ------------- | ------------- |
| [setAttribute()](#setattribute---add-a-single-attribute-to-a-tag)  | add a single attribute to a tag  |
| [setAttributes()](#setattributes---add-multiple-attributes-to-a-tag)  | add multiple attributes to a tag  |
| [removeAttribute()](#removeattribute---remove-a-single-attribute)  | remove a single attribute  |
| [removeAttributeValue()](#removeattributevalue---remove-specific-value-of-an-attribute)  | remove specific value of an attribute  |
| [prepend(), append()](#prepend-append---prepend-or-append-a-string-to-an-object-field-form-button)  | prepend or append a string to an object (field, form, button,..)  |
| [removePrepend(), removeAppend()](#removeprepend-removeappend---remove-a-markup-previously-added-to-an-object-field-form-button)  | remove a previously set markup from an object (field, form, button,..)  |
| [removeAttributeValue()](#removeattributevalue---remove-specific-value-of-an-attribute)  | remove specific value of an attribute  |


### setAttribute() - add a single attribute to a tag
You can add every attribute to an object by adding the attribute name and the value inside the parenthesis separated by a comma.

```php
  $field->setAttribute('id', 'myId');
```

For boolean attributes (like disabled, required,...) you only need to add the value.

```php
  $field->setAttribute('disabled');
```

For attributes that can have multiple values like classes, rel, style please write it in the way you write it in HTML, if you want to add more values at once or write it in each line.

```php
  // write in one line
  $field->setAttribute('class', 'class1 class2 class3');
  $field->setAttribute('style', 'color:yellow;font-weight:bold');

  // or write it on a separate line
  $field->setAttribute('class', 'class1');
  $field->setAttribute('class', 'class2');
  $field->setAttribute('class', 'class3');
```

### setAttributes() - add multiple attributes to a tag
You can also set multiple attributes at once, but you have to put the attributes inside an array.

```php
  $field->setAttributes(['id' => 'myId', 'class' => 'myClass']);
```

### removeAttribute() - remove a single attribute
You can remove an attribute by adding the attribute name inside the parenthesis. In this case you will remove the attribute completely.

```php
  $field->removeAttribute('class'); // this removes the class attribute completely from the tag
```

### removeAttributeValue() - remove specific value of an attribute
You can also remove a specific value of an attribute and not the attribute itself. This is only useful if you want to remove a value from a multi-value attribute (like the class attribute)

```php
  $field->removeAttributeValue('class', 'class1'); // this removes only the value class1 from the class attribute. All other class values stay untouched
```

### Attribute alias methods
For the most used attributes, an alias method exists. At the moment only for ID is an alias method included. Maybe others
will be added in the future.

```php
  // both methods are identical
  $field->setID('myID'); 
  $field->setAttribute('id','myID'); 
```

### prepend(), append() - prepend or append a string to an object (field, form, button,..)
You can prepend/append a string before and after an object. So you can add additional markup if you want.

```php
  $field->prepend('<div class="grid">')->append('</div>');  
```

### removePrepend(), removeAppend() - remove a markup previously added to an object (field, form, button,..)
This method can be used if a markup was set before via the prepend() or append() method and you want to remove it again.

```php
  $field->removePrepend();  
  $field->removeAppend(); 
```

## Form methods

The form object holds all the input fields, fieldsets, additional markup,...

```php
  // instantiating a new form object
  $form = new Form('myForm');
```

| Method name  | Use case | 
| ------------- | ------------- |
| [useFieldWrapper()](#usefieldwrapper---addremove-of-the-most-outer-container-tofrom-all-formfields)  | add/remove of the most outer container to/from all formfields  |
| [useInputWrapper()](#useinputwrapper---addremove-of-input-container-tofrom-all-formfields)  | add/remove of input container to/from all formfields  |
| [useHoneypot()](#usehoneypot---enabledisable-honeypot-field)  | enable/disable honeypot field  |
| [useDoubleFormSubmissionCheck()](#usedoubleformsubmissioncheck---enabledisable-double-form-submission-check)  | enable/disable double form submission check  |
| [disableCaptcha()](#disablecaptcha---remove-the-captcha-on-per-form-base)  | remove the CAPTCHA on per form base  |
| [setRequiredText()](#setrequiredtext---customize-text-for-required-fields)  | customize text for required fields  |
| [setRequiredTextPosition()](#setrequiredtextposition---change-required-text-position)  | change required text position  |
| [setMethod()](#setmethod---change-the-form-sending-method)  | change the form sending method (GET or POST)  |
| [setMinTime(), setMaxTime()](#setmintime-setmaxtime---change-minmax-time-for-submitting-a-form)  | change min/max time for submitting a form  |
| [setMaxAttempts(), setMaxTime()](#setmaxattempts---change-max-number-of-invalid-submission-attempts)  | change max number of invalid submission attempts  |
| [getValues(), setMaxTime()](#getvalues---get-all-_post-values-after-successfull-form-submission-as-array)  | get all $_POST values after successfull form submission as array  |
| [getValuesAsString()](#getvaluesasstring---get-all-_post-values-after-successfull-form-submission-as-a-string)  | get all $_POST values after successfull form submission as a string  |
| [getValue()](#getvalue---get-a-single-_post-value-by-its-name)  | get a single $_POST value by its name  |
| [add()](#add---add-a-field-to-the-form-object)  | add a field to the form object  |
| [remove()](#remove---remove-a-field-from-the-form-object)  | remove a field from the form object  |
| [getFormElements()](#getformelements---get-all-elements-of-the-form-input-buttons-fieldset-as-an-numeric-array-of-objects)  | Get all elements in the form (input, buttons, fieldset,..) as an numeric array of objects  |
| [getFormElementByName()](#getformelementbyname---get-a-specific-form-field-by-its-name)  | get a specific form field by its name  |
| [getFormElementsByClass()](#getformelementsbyclass---get-all-form-fields-of-a-specific-class)  | get all formfields of a specific class  |
| [setErrorMsg()](#seterrormsg---overwrite-the-default-error-message-after-form-submission)  | overwrite the default error message after form submission  |
| [setSuccessMsg()](#setsuccessmsg---overwrite-the-default-success-message-after-form-submission)  | overwrite the default success message after form submission  |
| [useFormElementsWrapper()](#useformelementswrapper---addrmove-an-additional-div-container-wrapper-over-all-form-fields)  | add/remove an additional div container (wrapper) over all form fields  |
| [getFormElementsWrapper()](#getformelementswrapper---get-the-form-elements-wrapper-object)  | get the form elements wrapper object  |
| [appendLabelOnCheckboxes(), appendLabelOnRadios()](#appendlabeloncheckboxes-and-appendlabelonradios-for-checkboxes-and-radio-buttons)  | for checkboxes and radio buttons  |
| [setUploadPath()](#setuploadpath---change-the-default-storage-location-for-uploaded-files)  | change the default storage location for uploaded files  |
| [useIPBan()](#useipban---enabledisable-checking-of-forbidden-ip-address)  | enable/disable checking of forbidden IP address  |
| [testIPBan()](#testipban---test-method-to-check-if-ip-ban-works-only-for-dev-purpose)  | This is only a testing method to check if IP banning works  |
| [logFailedAttempts()](#logfailedattempts---enabledisable-logging-of-ip-addresses-after-the-number-of-max-failed-attempts)  | enable/disable logging of IP addresses after the number of max failed attempts  |
| [isValid()](#isvalid---main-method-to-validate-the-form)  | main method to validate the form  |
| [isBlocked()](#isblocked---check-whether-a-visitor-is-blocked-or-not)  | check whether a visitor is blocked or not  |
| [showForm()](#showform---show-or-hide-the-rendering-of-the-form)  | show or hide the form  |
| [getShowForm()](#getshowform---get-the-value-truefalse-if-the-value-should-be-displayed)  | Get the value, whether the form should be displayed or not  |
| [render()](#render---output-the-markup-of-the-form)  | output the markup of the form  |
| [setSubmitWithAjax()](#setsubmitwithajax---use-ajax-for-form-submission)  | Depricated!! Use Ajax to submit a form without page reload  |
| [useAjax()](#useajax---use-ajax-for-form-submission)  | Use Ajax to submit a form without page reload  |
| [setRedirectURL()](#setredirecturl---redirect-to-another-page-after-form-submission)  | Add a redirect URL to the form submission  |
| [showProgressbar()](#showprogressbar---showhide-the-progress-bar-during-ajax-form-submission)  | Show or hide the progressbar during Ajax form submission  |
| [setRedirectUrlAfterAjax()](#showprogressbar---showhide-the-progress-bar-during-ajax-form-submission)  |DEPRICATED!! Redirect to another page after successfull form submission via Ajax  |
| [removeMultipleEntriesByClass()](#removemultipleentriesbyclass---delete-all-instances-of-a-form-element-of-a-given-class-except-the-last-one)  | delete all instances of a form element of a given class except the last one  |
| [formContainsElementByClass()](#formcontainselementbyclass---check-if-the-form-object-contains-at-least-one-form-element-of-the-given-class)  | check if the form object contains at least one form element of the given class  |
| [getElementsbyClass()](#getelementsbyclass---get-array-of-all-form-elements-of-the-given-class)  | get array of all form elements of the given class  |
| [useCSRFProtection()](#usecsrfprotection---enabledisable-csrf-protection-on-form)  | Enable or disable CSRF-Protection  |
| [useFormInCrossDomainIframe()](#useformincrossdomainiframe---use-forms-in-an-iframe-on-another-domain)  | Method to prepare a form to be usable inside an iframe on another domain  |
| [setDescPosition()](#setdescposition---change-the-position-of-field-descriptions)  | Set the position of the field description text  |
| [setCaptchaSuccessMsg()](#setcaptchasuccessmsg---add-a-success-message-under-the-captcha-field)  | Add a success message under the CAPTCHA field  |
| [setCaptchaErrorMsg()](#setcaptchaerrormsg---overwrite-the-default-error-message-of-the-captcha-validation-rule)  | Overwrite the default error message of the CAPTCHA validation rule  |
| [setCaptchaRequiredErrorMsg()](#setcaptcharequirederrormsg---overwrite-the-default-error-message-of-the-captcha-required-validation-rule)  | Overwrite the default error message of the CAPTCHA required validation rule |
| [setCaptchaNotes()](#setcaptcharequirederrormsg---overwrite-the-default-error-message-of-the-captcha-required-validation-rule)  | Overwrite the default notes of the CAPTCHA input field  |
| [setCaptchaDescription()](#setcaptchadescription---add-a-description-to-the-captcha-input-field)  | Add a description to the CAPTCHA input field  |
| [setCaptchaPosition()](#setcaptchaposition---place-the-captcha-on-a-position-of-your-choice-inside-the-form)  | Output the CAPTCHA at another position inside the form  |
| [setSimpleQuestionCaptchaRandomRotation() DEPRECATED](#setsimplequestioncaptcharandomrotation---showing-multiple-questions-randomly-instead-of-a-single-question-with-the-simple-question-captcha)  | Showing multiple questions randomly instead of a single question with the simple question CAPTCHA |
| [setSecurityQuestion()](#setsecurityquestion---enter-only-a-single-question)  | Enter a single question for the simple question CAPTCHA on per form base  |
| [setSecurityQuestions()](#setsecurityquestions---enter-multiple-questions-at-once)  | Enter multiple question at once for the simple question CAPTCHA on per form base  |
| [setCaptchaPlaceholder()](#setcaptchaplaceholder---add-a-placeholder-text-to-the-captcha-input-field)  | Add a placeholder text to the CAPTCHA input field |
| [removeCaptchaLabel()](#removecaptchalabel---hide-the-label-and-display-the-label-text-optionally-as-placeholder-text)  | Hide the label and display the label text optionally as placeholder text |
| [setLabelTag()](#setlabeltag---change-the-tag-for-the-label-on-this-form)  | Change the tag for the label on per form base |
| [setDescriptionTag()](#setdescriptiontag---change-the-tag-for-the-input-field-description-on-this-form)  | Change the tag for the input field description on per form base |
| [setNotesTag()](#setnotestag---change-the-tag-for-the-input-field-notes-text-on-this-form)  | Change the tag for the input field notes text on per form base |
| [setMsgTag()](#setmessagetag---change-the-tag-for-the-input-field-error-and-success-message-text-on-this-form)  | Change the tag for the success and error message on per form base |
| [stopHoneypotRotation()](#stophoneypotrotation---stop-the-randomly-rotation-of-the-honeypot-field-on-per-form-base)  | Stop the Honeypot field from randomly positioning and add it fixed to the very first position of the form |
| [setAjaxMessage()](#setajaxmessage---customize-the-info-text-during-an-ajax-submission-on-per-form-base)  | Customize the info text during an AJAX submission on per form base |
| [useJS()](#usejs---enabledisable-the-embedding-of-js-files-on-per-form-base)  | Enable/disable the embedding of JS files of this module on per form base |
| [useCSS()](#usecss---enabledisable-the-embedding-of-css-files-on-per-form-base)  | Enable/disable the embedding of CSS files of this module on per form base |

### useFieldWrapper() - add/remove of the most outer container to/from all formfields
Add/remove the [field-wrapper](#field-wrapper) container to/from all form fields by adding the appropriate boolean parameter.

```php
$form = new Form('myForm');
$form->useFieldWrapper(true); // add the field wrapper to all input elements
$form->useFieldWrapper(false); // remove the field wrapper from all input elements
```

### useInputWrapper() - add/remove of input container to/from all formfields
Add/remove the [input-wrapper](#input-wrapper) container to/from all form fields by adding the appropriate boolean parameter.

```php
$form = new Form('myForm');
// field wrapper
$form->useFieldWrapper(true); // add the field wrapper to all input elements - this is the defaults setting
$form->useFieldWrapper(false); // remove the field wrapper from all input elements
// same for input wrapper 
$form->useInputWrapper(true); // add the input wrapper to all input elements - this is the defaults setting
$form->useInputWrapper(false); // remove the input wrapper from all input elements
```


### useHoneypot() - enable/disable honeypot field
This will add or remove the honeypot field. Enter true or false as parameter

```php
  $form->useHoneypot(false); // this removes the honeypot field form the form
    $form->useHoneypot(true); // this will add the honeypot field to the form - this is the default setting
```

### useDoubleFormSubmissionCheck() - enable/disable double form submission check
This will enable/disable the checking of double form submission. This is useful on profile forms, where you can change
your data multiple times.

```php
  $form->useDoubleFormSubmissionCheck(true); // double form submission check will be enabled - this is the default setting
  $form->useDoubleFormSubmissionCheck(false); // double form submission check will be disabled on the form
```

### disableCaptcha() - remove the CAPTCHA on per form base
With this method you can disable the usage of CAPTCHA on per form base. This makes sense, fe if the user is logged in and you do not want to show the CAPTCHA inside his profile form.

```php
  $form->disableCaptcha();
```
### setRequiredText() - customize text for required fields
With this method you can overwrite the default hint that will be displayed on the form to inform the visitor that he has to fill all required fields marked with an asterisk.

```php
  $form->setRequiredText('Please fill out all required fields');
```

### setRequiredTextPosition() - change required text position
With this method you can overwrite the position of the required text in the global settings in the backend. As parameter, you have none, top or bottom. If set to top, the text will be displayed above the form, otherwise below. If you choose none, then the text will not be displayed at all.

```php
  $form->setRequiredTextPosition('bottom');
```

### setMethod() - change the form sending method
Set the form method (post, get). If you want to use post as your method, you do not need to add this method explicitly, because this method was set as the default method.

```php
  $form->setMethod('post');
```
### setMinTime(), setMaxTime() - change min/max time for submitting a form

Set the min and max time for form submission in seconds. The form will only be submitted if the submission time is in between the time range.

```php
  $form->setMinTime(5);
  $form->setMaxTime(3600);
```
### setMaxAttempts() - change max number of invalid submission attempts

Set the max number of attempts to submit a form successful. If the number of unsuccessful attempts is higher than the max number of attempts, the form submission will be blocked.

```php
  $form->setMaxAttempts(10);
```

### getValues() - get all $_POST values after successfull form submission as array
This method returns all form values after successful validation as an array. Use this method to process the values further (fe send via email).
By default, this method only returns values from inputfields. If you need values from buttons to, please add true inside the parenthesis.

```php
  $form->getValues();
```

```php
  $form->getValues(true); // this also outputs the value of a button (fe send) if needed
```
### getValuesAsString() - get all $_POST values after successfull form submission as a string
This method is the same as the getValues() method, but it returns all post values as a string instead of an array.

```php
  $form->getValuesAsString();
```
### getValue() - get a single $_POST value by its name
This will return the value of a specific input field after a successful form submission. You have to write the name of the input field inside the parenthesis.
```php
  $form->getValue('subject'); // this will return the value of the input field with the name attribute subject
```
### add() - add a field to the form object
This is the method to add a field to the form. You have to enter the field object inside the parenthesis.

```php
  $form->add($field);
```

### remove() - remove a field from the form object
This is the method to remove a field from the form. You have to enter the field object inside the parenthesis.

```php
  $form->remove($field);
```

### getFormelements() - Get all elements of the form (input, buttons, fieldset,..) as an numeric array of objects
If you need all elements of the form, you can use this method to get all elements as an object.

```php
  $form->getFormelements();
```


### getFormelementByName() - get a specific form field by its name
Grab a form element by its name attribute - returns the field object for further manipulation.
Fe if you want to get the field with the name attribute "email" add "email" as parameter inside the parenthesis, and you will get the form field object as return value.
```php
  $form->getFormelementByName($fieldname); // fieldname could be fe email, pass or whatever
```

### getFormelementsByClass() - get all form fields of a specific class
If you need all formelements of a given class (eg InputfieldText), this method is the right one - returns array containing all objects of the given class

```php
  $form->getFormelementByClass($classname); // classname must be the name of the class with or without namespace
  $form->getFormelementByClass('Button); // this outputs an array containing all button elements of the form object
```

### setErrorMsg() - overwrite the default error message after form submission
With this method you can overwrite the default error message which appears inside the alert box after an unsuccessful form submission.
```php
  $form->setErrorMsg('Sorry, but there are errors!');
```
### setSuccessMsg() - overwrite the default success message after form submission
With this method you can overwrite the default success message which appears inside the alert box after a successful form submission.
```php
  $form->setSuccessMsg('Congratulations, your message was submitted successfully!');
```
### useFormElementsWrapper() - add/remove an additional div container (wrapper) over all form fields
A user requested this functionality for usage with a specific framework (I cannot remember which one), so I have added this functionality.
With this method you can wrap all form fields in an extra div, or remove the wrapper.

```html
  <form>
   <div id="formelementswrapper">
      ....
   </div>
</form>
```
You only have to add this method to your form object with bool parameter
```php
  $form->useFormElementsWrapper(true); // adds the wrapper
  $form->useFormElementsWrapper(false); // removes the wrapper
```

### getFormElementsWrapper() - get the form elements wrapper object
This method returns the wrapper object for all form fields for fe further manipulations.

```php
  $form->getFormElementsWrapper();
```

### appendLabelOnCheckboxes() and appendLabelOnRadios() for checkboxes and radio buttons
By default, all checkboxes and radio buttons are wrapped(surrounded) by their label tag. This is what you can see in most CSS frameworks, but sometimes this is not the case and the labels should be appended to the input field instead.

Default case: Labels surrounded by the input tag

```html
<label><input type="checkbox">Checkbox Label</label>
```

Special case: Label will be appended after the input tag

```html
<input type="checkbox"><label>Checkbox Label</label>
```

You can set this behavior globally in the module configuration, but you can overwrite it on per form base if needed.

```php
$form->appendLabelOnCheckboxes(true); //appends the label after the input tag
$form->appendLabelOnCheckboxes(false); // the input tag will be wrapped by the label tag
```

You can do the same for radio buttons by using the appendLabelOnRadios() method.

### setUploadPath() - change the default storage location for uploaded files
If you are using a file upload field inside your form, all uploaded files will be in the site/assets/files/ directory inside a folder named after the id of the page where the form  is included. Fe the id of the page where the form is included is 1000, then the files will be stored inside site/assets/files/1000.
This is usually the way to go, but in rare cases you will need to store the files at an other location.
For this use case you can use this method.

```php
$form->setUploadPath('mycustomfolder/'); // the files will be stored at site/assets/files/mycustomfolder/
```

### useIPBan() - enable/disable checking of forbidden IP address
As written in the [security part](#measure-4-ip-blacklist), you can log every IP that did not submit a form within the allowed number of max attempts.
Each IP that has been logged, cannot view a form until it the logging
You can disable the checking of the IP address on each form manually.

```php
$form->useIPBan(true); // IP checking will be enabled on this form - this is the default value
$form->useIPBan(false); // IP checking will be disabled on this form
```
#### testIPBan() - test method to check if IP ban works (only for dev purpose)
This is only a testing method to check if IP banning works.
Enter an IP address inside the module configuration and then use this method on the form by entering the IP inside the parenthesis.

```php
$form->testIPBan('146.70.36.200'); // 
```
By visiting the page, an alert box will be displayed on the frontend instead of the form. Try it out to see how it works.

### logFailedAttempts() - enable/disable logging of IP addresses after the number of max failed attempts
Every visitor, which needs more than the allowed number of max attempts to submit a form, can be logged (written to the log files) if set in the module configuration.
You can enable/disable the logging of blocked visitors IP on per form base.

```php
$form->logFailedAttempts(true); // blocked visitors will be logged on this form
$form->logFailedAttempts(false); // no log entry will be written if visitors are blocked -> this is the default setting
```
### isValid() - main method to validate the form
This is the most important method. It takes the user data, sanitizes it, validates it and outputs possible errors or the success message.
Returns true or false after form submission. You have to use this method to process the submitted form data further.
```php
  $form->isValid();
```

### isBlocked() - check whether a visitor is blocked or not
Every visitor, which needs more than the allowed number of max attempts to submit a form, will be blocked by using a session. You will find more info about this [here](#measure-1-set-max-number-of-invalid-attempts).
If you want to do another logic if a visitor was blocked, then use the isBlocked() method and run your code inside it.
Only to mention: A visitor will be blocked if the max number of attempts to submit the form with success was reached.

```php
  $form = new Form('myForm');
  $form->setMaxAttempts(10);
  ....
  if($form->isBlocked()){
    .....
  }
  $form->render()
```

### showForm() - Show or hide the rendering of the form

```php
  $form->showForm(true);// true or false
```

This method is not designed for daily use. It is more a method that can be used for devs to manipulate the displaying of the form under certain conditions.

Example: If you have submitted a form and the form is valid, the usual behaviour is that you will get a success message, but the form will not be displayed any longer. With this method you can force the displaying of the form after submission, even if the form is valid. You only need to add this method inside the isValid() method.

### getShowForm() - Get the value (true/false) if the value should be displayed

```php
  $form->getShowForm();// returns true or false
```

This method is only the Getter method of the previous method, where you can check the status of the form displaying.

This method is also not interesting for daily use.

### render() - output the markup of the form
Render the form on the page.

```php
  echo $form->render();
```

### setSubmitWithAjax() - use Ajax for form submission
**THIS METHOD STILL WORKS, BUT IT IS DEPRICATED. PLEASE USE THE NEW METHOD useAjax() INSTEAD!**

Enable/disable Ajax form submission by using this method. The global value is set inside the module configuration,
but you can overwrite this value by using this method. Enter "true" to enable or "false" to disable Ajax support inside the parenthesis.

```php
  $form->setSubmitWithAjax(true); // true or false
```

### useAjax() - use Ajax for form submission
Enable/disable Ajax form submission by using this method. The global value is set inside the module configuration,
but you can overwrite this value by using this method. Enter "true" to enable or "false" to disable Ajax support inside the parenthesis.
This is an alias of the old setSubmitWithAjax() method.

```php
  $form->useAjax(true); // true or false
```

## setRedirectURL() - Redirect to another page after form submission
You can use this method to redirect to another page after the form has been validated and the code between the isValid() condition has been executed.
It does not matter if you are submitting the form via Ajax or not.

```php
  $form->setRedirectURL('path or url/to/the/redirect page'); // enter the path or the URL to the redirect page
```

### showProgressbar() - show/hide the progress bar during Ajax form submission
An animated progress bar shows the user, that the form will be validated after an Ajax form submission. Otherwise, the user will not see, that something happens behind the scenes.
The global value (show or hide the bar) is set inside the module configuration,
but you can overwrite this value by using this method. Enter "true" to show or "false" to hide the progress bar.

```php
  $form->showProgressbar(true); // true or false
```

### setRedirectUrlAfterAjax() - redirect to another page after successful form submission via Ajax
**THIS METHOD STILL WORKS, BUT IT IS DEPRICATED! PLEASE USE THE setRedirectURL() INSTEAD!!**

If you are using Ajax to submit a form, a PHP session redirect will not work, if you want to redirect to another page after successful form submission. In this case, you have to do a JavaScript redirect. 
This method will force JavaScript to redirect to another page - you only have to add the new page URL inside the parenthesis.

```php
  $form->setRedirectUrlAfterAjax('/blog/');
```

### removeMultipleEntriesByClass() - delete all instances of a form element of a given class except the last one
If you want to allow only 1 instance of a given form element inside a form, you can clean up the form element array by removing multiple instances of the given element except the last one.

I have used it for the "Privacy" and the "PrivacyText" class, which are there to add a "Accept the privacy" checkbox or text before the submit button. It would not make sense to add multiple instances of these class elements to a form.

If you try to add multiple instances to a form object, all instances except 1 will be deleted automatically. So this method is usefull for this rare case scenario, then for every day usage.

```php
  $form->removeMultipleEntriesByClass('Privacy'); // enter the class name without namespace
```

### formContainsElementByClass() - check if the form object contains at least one form element of the given class
This method returns the number of objects of the given class found inside the form object or 0 if no one was found.

```php
  $form->formContainsElementByClass('Privacy'); // enter the class name without namespace
```

### getElementsbyClass() - get array of all form elements of the given class
This method returns a numeric array of all form elements of a given class inside the form object.

```php
  $form->getElementsbyClass('Privacy'); // enter the class name without namespace
```

### useCSRFProtection() - Enable/disable CSRF-Protection on form
This method offers the possibility to disable the CSRF-Protection on a form if needed. By default this security check is always enabled.

```php
  $form->useCSRFProtection(false); // parameter false to disable CSRF-Protection
```

### useFormInCrossDomainIframe() - Use forms in an iframe on another domain

This method is the method, you need to use, if you want to use the form in an iframe on another domain (not on the same domain). The problem is that forms are using sessions to work and you cannot get this session values if you are integrating a form inside an iframe on another domain (crossdomain session problem).

This method disables CSRF-protection, the check for double form-submission and the CAPTCHA if set. Each of them uses sessions and cannot be used inside an iframe. If they will not be disabled, the form submission will not work.

So using this method on the form object makes it possible to use a form inside an iframe on another domain. If the iframe is on the same domain, everything works as expected and you do not need to use this method. So this method is only for the rare case of crossdomain iframe usage.

```php
  $form->useFormInCrossDomainIframe(); // parameter false to disable CSRF-Protection
```

### setDescPosition() - Change the position of field descriptions

With this method you can change the position of each input field description on the form. This will overwrite the global settings from the module configuration.

* beforeLabel: The description will be displayed above (before) the label
* afterLabel: The description will be displayed below (after) the label
* afterInput: The description will be displayed below (after) the input field

```php
  $form->setDescPosition('afterLabel'); // possible parameters: beforeLabel, afterLabel, afterInput
```

### setCaptchaSuccessMsg() - Add a success message under the CAPTCHA field
The Captcha field will be added automatically to the form if set. For this reason you have no possiblity to add or remove attributes or markup by yourself by default.

To offer the possibility to output a success message info after the form input for the CAPTCHA if the CAPTCHA value was correct, you can use this method.
Please note: A user will only see this success message, if there are other errors on the form. All CAPTCHAS, except the single question CAPTCHA does not hold the entered value inside the input field, after the form subission, so the user has to enter the CAPTCHA value again. In this case a message like "Well done, your value was correct, but you need to entere the CAPTCHA value again" is better than only using fe "well done".

```php
  $form->setCaptchaSuccessMsg('Well done, your value was correct, but you need to entere the CAPTCHA value again');
```
This success message is the opposite of an error message and was introduced to input elements in version 2.1.70. With this method you can add it to CAPTCHA inputs as well, if you want.

### setCaptchaErrorMsg() - Overwrite the default error message of the CAPTCHA validation rule 

This method is for overwriting the default error message for the captcha validation, that will be displayed if the value entered is not correct. 

Which error message will be displayed as the default error message depends on the CAPTCHA choosen. This method works with all CAPTCHA types and let you customize this error message

```php
$form->setCaptchaErrorMsg('The value you have entered is not correct!');
```

### setCaptchaRequiredErrorMsg() - Overwrite the default error message of the CAPTCHA required validation rule 

This method is for overwriting the default error message for the captcha required validation, that will be displayed if the value entered is missing. 
Every CAPTCHA has this validation rule added by default.

You can use this method, if you do not like the default error message for the required rule.

```php
$form->setCaptchaRequiredErrorMsg('The CAPTCHA field must be filled out!');
```

### setCaptchaPlaceholder() - Add a placeholder text to the CAPTCHA input field 

This method works only on input fields of the type text. The image captcha uses input radios and therefore this method has no effect. 

```php
$form->setCaptchaPlaceholder('Please fill in this field');
```
### setCaptchaNotes() - Overwrite the default notes of the CAPTCHA input field
This method is the same as the setNotes() method for input fields, but in this case especially for the CAPTCHA inputfield. It allows you to overwrite the default notes text with your own.

```php
$form->setCaptchaNotes('This is my CAPTCHA notes text.');
```

### setCaptchaDescription() - Add a description to the CAPTCHA input field
This method is the same as the setDescription() method for input fields, but in this case especially for the CAPTCHA inputfield. It allows you to add a description text to the CAPTCHA input field.

```php
$form->setCaptchaDescription('This is my CAPTCHA description text.');
```

### setCaptchaDescriptionPosition() - Set the position of the description of the CAPTCHA input field individually
This method is the same as the setPosition() method for the input field description, but in this case especially for the CAPTCHA inputfield. It allows you to position the description text of the CAPTCHA input field independently of the global settings.

Possible values are: beforeLabel, afterLabel and afterInput.

```php
$form->setCaptchaDescriptionPosition('beforeLabel');
```

### setCaptchaPosition() - Place the CAPTCHA on a position of your choice inside the form

The CAPTCHA is a so called "system field", which means, that it will be added by the module and not by the user. By default, the CAPTCHA will be placed before the submit button. This is fine in most cases, but if you want to position the CAPTCHA on another position, you can use this method.

With this method, you are able to position the CAPTCHA on every position inside the form by defining a reference field and the position attribute "before" or "after".

The method must be applied to the form object and must contain as first parameter the name of the reference field (fe. email) and as second parameter the attribute "before" or "after".

```php
$form`->setCaptchaPosition('email', 'after');
```

This example places the CAPTCHA **after** the field with the name attribute "**email**".

### removeCaptchaLabel() - Hide the label and display the label text optionally as placeholder text
By default the CAPTCHA label will be displayed next to the CAPTCHA input, which is fine in most cases.

If you want to suppress the display of the label you can use this method. Optional you can select, if the label text should be displayed as the placeholder text.
In this case you have to set the parameter true inside the parenthesis.

```php
$form`->removeCaptchaLabel(true);// if set to true, then the label text will be displayed as placeholder, otherwise the label will only be removed
```

### setSimpleQuestionCaptchaRandomRotation() - Showing multiple questions randomly instead of a single question with the simple question CAPTCHA
This method is DEPRECATED and has been replaced by the setSecurityQuestions() method. It works, but should not be used any longer.

Take a look at the next method: setSecurityQuestions().

### setSecurityQuestions() - Enter multiple questions at once
This method does the same as setSimpleQuestionCaptchaRandomRotation() method, which is deprecated.

Please note: This method is for usage with the simple question CAPTCHA only!

By default, the simple question CAPTCHA supports only 1 single question to be displayed, which can be entered inside the module configuration. If you want to show more questions that changes randomly on every page load, you have to use the [*FrontendForms Manger*](#custom-module-frontendformsmanager) user-interface and enter your questions there or you can use this method to extend the single question CAPTCHA to a multi-question CAPTCHA.

All the questions have to be defined inside an multidimensional assoc. array containing a specific structure as described below.

Here is an example of such a multi-question array:

```php
$questions = [
    [
        'question' => 'How many eyes does a person have?',
        'answers' => ['2', 'two', '2 eyes', 'two eyes'],
        'errorMsg' => 'Unfortunately not the right answer! Take a look at the mirror ;-).',
        'successMsg' => 'Perfect! You know how humans look like.',
    ],
    [
        'question' => 'How many legs does a dog have?',
        'answers' => ['4', 'four', '4 legs', 'four legs'],
        'notes' => 'Tip: A dog has more than 3 but less than 5 legs',
        'successMsg' => 'Yes you are absolute right! (Most) Dogs have 4 legs.',
        'errorMsg' => 'Not really! It seems that you do not have seen a dog before ;-).',
    ],
    [
        'question' => 'What is the last day of the week called?',
        'answers' => ['Sunday', 'its Sunday', 'it is Sunday'],
        'notes' => 'A little tip: S**day',
        'errorMsg' => 'No! Not really. A look at a calendar would probably help ;-).',
        'successMsg' => 'It is Sunday! Absolut correct!',
    ],
    [
        'question' => 'How many halves make a whole?',
        'answers' => ['2', 'two', '2 halves', 'two halves']
    ],

];
```

Each question is an extra array inside the array and must have at least the **question** and the **answers** key as the minimum requirements.

The following keys are optional and can be set or not:

* errorMsg: define a custom error message for this question
* successMsg: define a custom success message for this question
* notes: define a custom notes text for this question
* description: define a custom description text for this question
* descriptionPosition: define an individual position for the description for this question (beforeLabel, afterLabel, afterInput)
* placeholder: define a custom placeholder text for the input field

The array containing the questions has to be entered as the parameter inside this function:

```php
$form`->setSecurityQuestions($questions);
```

You can also use this method side by side with the questions entered inside the FrontendForms Manager.

You can take a look at the [examples folder](https://github.com/juergenweb/FrontendForms/blob/main/Examples/setmultiplequestionsforcaptcha.php), where you can find a working example using this method.

### setSecurityQuestion() - Enter only a single question
Instead of adding multiple questions at once via an multi-level array as described in the previous method, you can add a single question with this method to the CAPTCHA (independent if you use the FrontendForms Manger or not).

Please note: This method is for usage with the simple question CAPTCHA only!

```php
$form`->setsetSecurityQuestion('My question', ['answer 1', 'answer 2', 'answer 3']);
```
You have also the possibility to add various parameter (eg notes text, description text etc) as the third parameter (assoc. array).

```php
$form`->setsetSecurityQuestion('My question', ['answer 1', 'answer 2', 'answer 3'], ['notes' => 'This is my notes text', 'description' => 'My description text']);
```

For all possible parameters take a look at the setSecurityQuestions() method which has been described before.

### setLabelTag() - Change the tag for the label on this form

Wtih this method you can overwrite the global tag for the lable on per form base.

```php
$form`->setLabelTag('div');
```

### setDescriptionTag() - Change the tag for the input field description on this form

Wtih this method you can overwrite the global tag for the input field description on per form base.

```php
$form`->setDescriptionTag('div');
```

### setNotesTag() - Change the tag for the input field notes text on this form

Wtih this method you can overwrite the global tag for the input field notes text on per form base.

```php
$form`->setNotesTag('small');
```

### setMessageTag() - Change the tag for the input field error and success message text on this form

With this method you can overwrite the global tag for the input field messages on per form base.

```php
$form->setMessageTag('div');
```

### stopHoneypotRotation() - Stop the randomly rotation of the Honeypot field on per form base

With this method you can stop the randomly rotation of the Honeypot field. The Honeypot field will be positioned fixed on the very first position of the form. This can be used if the rotation of the Honeypot field breaks the layout of your form (should not happen, but it could be in very rare cases).

```php
$form->stopHoneypotRotation(true); // default is false
```

### setAjaxMessage() - Customize the info text during an AJAX submission on per form base

With this method you can change the text, that will be displayed next to the progress bar during an AJAX form submission.
By default the following text will be displayed: 'Please be patient... the form will be validated!'.
This can be changed inside the module configuration, where you can overwrite this default text by your own.
This method overwrites either the default text or the text set inside the module configuration on the form itself.

```php
$form->setAjaxMessage('Submitting your enquiry...');
```

### useJS() - Enable/disable the embedding of JS files on per form base

With this method you can enable/disable the embedding of the modules JS files on the frontend on per form base. If you have multiple forms on a page, then it
will be checked, which form has the embedding of the JS file enabled and wich not. If there is at least 1 form with enabled JS files, then the JS files will be embedded
on the frontend (independent of the setting of the other forms on the page).

So this is an API method, to set the embedding of the Javascript files directly on the form and not only in the backend.

```php
$form->useJS(false); // enable (true) or disable (false) the embedding of the modules JS files on the frontend (default is true)
```

### useCSS() - Enable/disable the embedding of CSS files on per form base

With this method you can enable/disable the embedding of the modules CSS file on the frontend on per form base. If you have multiple forms on a page, then it
will be checked, which form has the embedding of the CSS file enabled and wich not. If there is at least 1 form with enabled CSS file, then the CSS file will be embedded
on the frontend (independent of the setting of the other forms on the page).

So this is an API method, to set the embedding of the CSS file directly on the form and not only in the backend.

```php
$form->useCSS(false); // enable (true) or disable (false) the embedding of the modules CSS file on the frontend (default is true)
```

## Input field methods

For better understanding of methods explained afterwards, take a look of the anatomy of input fields first.

### Anatomy of input fields

```html
<div class="uk-margin" id="validationform-test1-fieldwrapper"> <!-- This is the field wrapper -->
   <label class="uk-form-label required" for="validationform-test1">Test required<span class="asterisk">*</span></label> <!-- The label -->
   <div class="uk-form-controls" id="validationform-test1-inputwrapper"> <!-- This is the input wrapper -->
      <input id="validationform-test1" name="test1" type="text" class="uk-input" required=""> <!-- The input field -->
      <p class"uk-text-error">This would be the error text on validation errors</p> <!-- The error message text -->
   </div>
   <p class="notes">Field is required</p> <!-- The notes text -->
   <p class="description">This is my field description</p> <!-- The field description text -->
</div>
```

#### Field wrapper
The field wrapper is the most outer container. You can enable/disable it in the global settings in the backend. But you can overwrite the global settings on each form individually by using the [useFieldwrapper()](#usefieldwrapper-getfieldwrapper) method at the form object.
If you want to customize the field wrapper you can use the [getFieldwrapper()](#usefieldwrapper-getfieldwrapper) method which returns the field wrapper object itself.

#### Input wrapper
This is a container element around the input field. You can set or remove it in the same way as the field wrapper by using the [useInputWrapper()](#usefieldwrapper-useinputwrapper) and [getInputWrapper()](#getfieldwrapper-getinputwrapper) methods.

#### Label, notes, description
These elements do not need a more detailed explanation. Only to mention here: you can customize all of them by chaining methods to set/remove attributes.

### General Methods for all input fields
These methods can be used on each input field independent of the input type.

| Method name  | Use case | 
| ------------- | ------------- |
| [useInputWrapper()](#useinputwrapper---add-or-remove-the-inputwrapper-on-per-field-base)  | add or remove the inputwrapper on per field base  |
| [useFieldWrapper()](#usefieldwrapper---add-or-remove-the-outer-fieldwrapper-on-per-field-base)  | add or remove the outer fieldwrapper on per field base  |
| [getFieldWrapper()](#getfieldwrapper---get-the-most-outer-container-of-a-formfield-object)  | get the most outer container of a formfield object  |
| [getInputWrapper()](#getinputwrapper---get-the-container-element-surrounding-the-input-field)  | get the container element surrounding the input field  |
| [setLabel()](#setlabel---add-the-label-text)  |  add the label text  |
| [setNotes()](#setnotes---add-text-for-notes-under-the-input-field)  |  add text for notes under the input field  |
| [setDescription()](#setdescription---add-a-description-text-to-an-input-field)  |  add a description text to an input field  |
| [setSanitizer()](#setsanitizer---set-a-processwire-sanitizer)  |   set a ProcessWire sanitizer  |
| [hasSanitizer()](#hassanitizer---check-if-a-special-sanitizer-was-set-to-the-field)  |   check if a special sanitizer was set to the field  |
| [getSanitizers()](#getsanitizers---get-all-sanitizers-set-to-a-the-field)  |   get all sanitizers set to a the field  |
| [removeSanitizers()](#removesanitizers---remove-all-previously-set-sanitizers)  |   remove one or more sanitizers  |
| [setRule()](#setrule---set-a-validation-rule-to-a-field)  |    add a validation rule to a field  |
| [getRules()](#getrules---get-all-previously-set-validation-rules-of-a-field)  |    get all previously set validation rules of a field  |
| [removeRule()](#removerule---remove-a-specific-validation-rule-from-a-field)  |     remove a specific validation rule from a field  |
| [removeAllRules()](#removeallrules---remove-all-previously-set-validation-rules-from-a-field)  |     remove all previously set validation rules from a field  |
| [hasRule()](#hasrule---check-if-a-field-has-a-specific-validation-rule-set)  |     check if a field has a specific validation rule set  |
| [getErrorMessage()](#geterrormessage---get-the-error-message-object-of-a-field-for-further-manipulations)  |     get the error message object set by the validator  |
| [setDefaultValue()](#setdefaultvalue---set-a-default-value-for-an-input-field-on-page-load)  |     set a default value for an input field on page load  |
| [setChecked()](#setchecked---set-default-value-for-single-checkboxes-without-values)  |     set default value for single checkboxes without values  |
| [render() ](#render---output-the-markup-of-an-input-field)  |     output the markup of an input field  |
| [setPosition() ](#setposition---change-the-position-of-field-description-directly-at-the-input-field)  |     change the description text position directly at the input field |
| [useCustomWrapper() ](#usecustomwrapper---add-a-custom-wrapper-to-an-inputfield)  |     add a custom wrapper to an inputfield  |
| [setSuccessMessage()](#setsuccessmessage---display-a-message-after-form-submission-and-the-entered-field-value-is-valid)  | display a successmessage under an inputfield if the value entered is correct after form submission a  |

### useInputWrapper - add or remove the inputwrapper on per field base
This is the same as the useInputWrapper() method of the form, but in this case you can add or remove the inputwrapper on per field base indpendent of what is set on per form base.

```php
$field->useInputWrapper(true); // can be true or false
```

### useFieldWrapper - add or remove the outer fieldwrapper on per field base
This is the same as the useFieldWrapper() method of the form, but in this case you can add or remove the fieldwrapper on per field base indpendent of what is set on per form base.

```php
$field->useFieldWrapper(true); // can be true or false
```

### getFieldWrapper() - get the most outer container of a formfield object
Get the fieldwrapper object for form fields for further manipulations
The getFieldWrapper() method returns the Field wrapper object, so you can manipulate it, if you need.

```php
$field->getFieldWrapper()->setAttribute('class', 'newClass')->removeAttribute('class', 'oldClass'); // customize the wrapper object
```

### getInputWrapper() - get the container element surrounding the input field
With this method you can grab the wrapper object for further manipulations, if needed

```php
// input wrapper object
$field->getInputWrapper()->setAttribute('class', 'newClass')->removeAttribute('class', 'oldClass'); // customize the input wrapper object
// and the same for the field wrapper object
$field->getFieldWrapper()->setAttribute('class', 'newClass')->removeAttribute('class', 'oldClass'); // customize the field wrapper object
```

#### setLabel() - add the label text
Method to add a label to the form field. Returns a label object.

```php
$field->setLabel('E-Mail address');
```

#### setNotes() - add text for notes under the input field
Method to add notes to the form field. Returns a notes object.

```php
$field->setNotes('You have to fill out this field');
```

#### setDescription() - add a description text to an input field
Method to add a description to the form field. Returns a description object.

```php
$field->setDescription('This text describes the input field more in detail');
```

#### setSanitizer() - set a ProcessWire sanitizer
Method to add a sanitizer to the form field. Returns a sanitizer object. You can use all ProcessWire sanitizer methods by adding the sanitizer name inside the parenthesis of the setSanitizer() method. You can also set multiple sanitizer methods to one field if necessary.

**Please note:**

For security reasons, 1 sanitizer will be applied to each input field automatically, so you do not have to add it manually. You can see the list which sanitizer will be added to a input field in the [table at the supported input types](#which-input-types-are-supported).

```php
$field->setSanitizer('text');
```

#### hasSanitizer() - check if a special sanitizer was set to the field
If you want to check if an input field has the given sanitizer, you can use this method.
Returns true or false.

```php
$field->hasSanitizer('text');
```

#### getSanitizers() - get all sanitizers set to a the field
If you want to know, which sanitizers are added to a field, you can use this method which returns an array containing
the names of all sanitizer added to this field.

```php
$field->getSanitizers();
```

#### removeSanitizers() - remove all previously set sanitizers
You can remove all sanitizers (including the sanitizers applied by default) with this method if you enter no value inside the parenthesis.
By entering one sanitizer as a string you can remove only this one.
By entering multiple sanitizers as an array, you can remove multiple sanitizers at once.

```php
$field->removeSanitizers(); // removes all sanitizers from this input field
$field->removeSanitizers('text'); // removes only the text sanitizer from this input field
$field->removeSanitizers(['text', 'number']); // removes multiple sanitizers from this input field
```

#### setRule() - set a validation rule to a field
Method to add a validator to the form field. You can find examples of all validators in the validationTypes.php inside the 'examples' folder. Add the name of the validator inside the parenthesis of the setRule() method. You can also set multiple validation methods to one field if necessary.

```php
$field->setRule('required');
$field->setRule('number');
```

#### getRules() - get all previously set validation rules of a field
Method to get all validators of a form field.
This is especially useful, if you want to know which validators are set to a field.
Most of the input fields have validators set by default, so you do not need to add them manually.
This method will turn an array with the names of all validators set to this field.

```php
$field->getRules();
```

#### removeRule() - remove a specific validation rule from a field
This is the opposite of setRule(). You can remove an unwanted rule with this method. This is useful if you use ['default fields'](#default-fields), which contains some validation rules by default.

```php
$field->removeRule('required'); // this removes the required validator from the field
```

#### removeAllRules() - remove all previously set validation rules from a field
With this method, you can remove all previously set validation rules from a field at once

```php
$field->removeAllRules(); // this removes all validation rules from the field
```

#### hasRule() - check if a field has a specific validation rule set
If you want to know if a certain field has a specific rule added, you can use this method. Add the name of the rule as
the parameter inside the parenthesis and the method returns boolean true or false.

```php
$field->hasRule('required');
```

#### getErrorMessage() - get the error message object of a field for further manipulations
You can use this method to manipulate attributes of the error message on per field base. Returns the error message object for further manipulations.

```php
$field->getErrorMessage()->setAttribute('class', 'myErrorClass');
```

#### setDefaultValue() - set a default value for an input field on page load
Set pre-defined values on page load to each input field. If it is an input field that can contain more than 1 value (fe select multiple, checkbox multiple), you can add multiple values separated by a comma.
Another possibility is to add 1 or multiple values as an array instead of a string.
Be aware: This works only on input fields with a value set (not fe on checkboxes with no value).

```php
// one value as a string
$singlefield = new InputText('name')
$singlefield->setDefaultValue('John');
```

```php
// one value as an array
$singlefield = new InputText('name')
$singlefield->setDefaultValue(['John']);
```

```php
// multiple values as a string separated by commas
$multifield = new InputCheckboxMultiple('hobbies')
$multifield->setDefaultValue('Tennis', 'Polo', 'Swimming');
```

```php
// multiple values as an array
$multifield = new InputCheckboxMultiple('hobbies')
$multifield->setDefaultValue(['Tennis', 'Polo', 'Swimming']);
```

There is no wrong or right: it depends on your preference of writing.

#### setChecked() - set default value for single checkboxes without values
As written above, setDefaultValue() can only be used for inputs with values.
Single checkboxes do not need values. To make them checked by default, you have to use
the setChecked() method instead of the setDefaultValue() method.
Using this method is necessary and the only way to mark checkboxes without a value attribute by default.

**Please note:**

This method works on checkboxes with value too, but in this case it is
recommended for consistency to use the getDefaultValue() method.

```php
// checkbox with no value
$checkbox = new Checkbox('singlecheckbox')
$checkbox->setChecked();
```

#### render() - output the markup of an input field
Method to render an input field. You do not need this method until you want to render an input field on its own.

```php
echo $field->render();
```

### setPosition() - Change the position of field description directly at the input field

With this method you can change the position of the input field description directly on the input field. This will overwrite the global and form settings.

You can choose between 3 different positions as parameter:

* beforeLabel: The description will be displayed above (before) the label
* afterLabel: The description will be displayed below (after) the label
* afterInput: The description will be displayed below (after) the input field

This method has been added to the Description object, so please chain it directly to it after you have set the description text:

```php
  $field->setDescription('My field description')->setPosition('afterLabel'); // possible parameters: beforeLabel, afterLabel, afterInput
```

Here is a complete real world example:

```
$file1 = new \FrontendForms\InputFile('fileupload1');
$file1->showClearLink(true); // show an link to empty the input field under the input field
$file1->setLabel('Multiple files upload');
$file1->setDescription('Description fileupload1')->setPosition('afterInput'); // This is how you add the position directyl to the Description object
$file1->setRule('allowedFileSize', '60000');
$file1->setRule('allowedFileExt', ['jpg','pdf']);
...
...
```
 
 ### useCustomWrapper() - Add a custom wrapper to an inputfield

With this method you can add an extra wrapper container to an inputfield. This container is the most outer container and can be used to for styling purposes or for detection with Javascript.

Inside the parenthesis you can add boolean values (true, false). If you add true or nothing, then the wrapper will be added. On the other side, if you add false, then the custom wrapper will be removed, if it has been added before.

```
$field = new \FrontendForms\InputText('text1');
$field->useCustomWrapper(); // this adds the custom wrapper, if you add false inside the parenthesis, then the custom wrapper will be removed
```

The custom wrapper is a div container with no attributes by default, but you can add attributes too by chaining them after this method:

```
$field = new \FrontendForms\InputText('text2');
$field->useCustomWrapper()->setAttribute('id', 'custom')->setAttribute('class', 'mycustomclass'); 
```
 ### setSuccessMessage() - Display a message after form submission and the entered field value is valid

 This is the opposite of the error message and will be displayed under the inputfield too. So if you define a success message for an inputfield and the entered value is valid after form submission, you can show a success message under the inputfield.

 ```
$field = new \FrontendForms\InputText('text2');
$field->setSuccessMessage('This value is valid'); 
```

Please note: This message will only be displayed after form submission, not on page load.
 
### Special Methods for special input fields
These methods can only be used on certain input fields and not at all.

| Method name  | Use case | 
| ------------- | ------------- |
| [alignVertical()](#alignvertical---set-the-alignment-for-checkboxes-and-radio-buttons)  | set the alignment for checkboxes and radio buttons  |
| [addOption() ](#addoption---add-option-tags-for-checkboxes-radio-buttons-select-and-datalist)  | add option tags for checkboxes, radio buttons, select and datalist  |
| [addEmptyOption()](#addemptyoption---add-an-empty-option-at-the-top-for-select-and-select-multiple)  | add an empty option at the top for select and select multiple  |
| [removeOptionByValue()](#removeoptionbyvalue---remove-an-option-element-with-a-specific-value) | remove an option element with a specific value from an element containing options (fe a select input)
| [setOptionsFromField()](#setoptionsfromfield---use-options-from-a-processwire-field-for-select-multiple-radio-multiple-checkbox-multiple-and-datalist-input-fields)  | use options from a ProcessWire field for select multiple, radio multiple, checkbox multiple and datalist input fields  |
| [showPasswordRequirements()](#showpasswordrequirements---show-the-password-requirements-under-a-password-field)  | show the password requirements under a password field  |
| [showPasswordToggle()](#showpasswordtoggle---show-a-checkbox-to-make-the-password-value-visible-next-to-a-password-field)  | show a checkbox to make the password value visible next to a password field  |
| [sendAttachment()](#sendattachment---send-files-via-the-wiremail-class)  | send files via the WireMail class  |
| [allowMultiple()](#allowmultiple-for-file-input-fields)  | add support for multiple file uploads on input type file  |
| [mailTemplate()](#mailtemplate---changedisable-the-usage-of-an-email-template-manually)  | change/disable the usage of an email template manually  |
| [showClearLink()](#showclearlink---show-or-hide-a-link-to-clear-a-file-input-field)  | show/hide a link under the file input field to clear the input  |
| [getClearLink()](#getclearlink---get-the-link-object-described-in-the-previous-method-for-further-manipulations)  | get the link object for the clear input link for further manipulations  |
| [useCharacterCounter()](#usecharactercounter---add-a-reverse-character-counter-below-a-textarea-if-maxlength-validator-is-set)  | add a character counter to a textarea if lengthMax validator is set  |
| [getCharacterCounter()](#getcharactercounter---get-the-character-counter-object-described-in-the-previous-method-for-further-manipulations)  | get the character counter object for further manipulations  |
| [addHorizontalRule()](#addhorizontalrule---add-a-hr-tag-to-select-input-fields-to-help-visually-break-up-the-options-for-a-better-user-experience)  | add hr tag to input selects for visually break up the options for a better user experience  |

#### alignVertical() - set the alignment for checkboxes and radio buttons
This is only a method for multiple checkboxes and radio buttons to align them vertical instead of horizontal (default).

```php
$checkbox = new InputCheckboxMultiple('myCheckbox')
$checkbox->alignVertical();
```

#### addOption() - add option tags for checkboxes, radio buttons, select and datalist
Method for multiple checkboxes, multiple radio buttons, select and datalist inputs to add an option element. As parameters, you have to add the label as first and the value as second parameter. Afterwards is an example with a multiple checkbox.

```php
$checkbox = new InputCheckboxMultiple('myCheckbox');
$checkbox->addOption('Checkbox 1', '1');
$checkbox->addOption('Checkbox 2', '2');
```

#### addEmptyOption() - add an empty option at the top for select and select multiple

```php
$select = new SelectMultiple('mySelect');
$select->addEmptyOption('Please select your choice');
$select->addOption('Value 1', '1');
$select->addOption('Value 2', '2');
```
By adding the addEmptyOption() method, the first option in line will not be selected by default. You do not need to add a text inside the parenthesis. By default, "-" will be shown.
Just add it to your select to see how it works.

#### removeOptionByValue() - remove an option element with a specific value

```php
$select->removeOptionByValue('1)';
```
By using this method on an element containing options (fe a select input), you can remove a previously added option element with a a specific value. In this example the option element with the value "1" will be removed from a given select input element.

#### setOptionsFromField() - use options from a ProcessWire field for select multiple, radio multiple, checkbox multiple and datalist input fields

```php
$select = new SelectMultiple('myField');
$select->setOptionsFromField('mypwfieldname');
```

With this method you can use the options from a ProcessWire field of the type FieldtypeOptions for a datalist, checkbox multiple, radio multiple or select field.
You only have to enter the name of the PW field and the field must be of the type FieldtypeOptions.
Otherwise, it will not work.

#### showPasswordRequirements() - show the password requirements under a password field
This method can be added to a password field and shows the password conditions set on the password field in the backend (fe has to contain digit uppercase, digit lowercase,...) under the input field.
By default, the requirements of the password field with the name "pass" will be shown. If your password field has another name, please insert the name as parameter to the method.

```php
$password1 = new InputPassword('password1');
$password1->showPasswordRequirements(); // the values from the field "pass" will be used by default
```
```php
$password2 = new InputPassword('password2');
$password2->showPasswordRequirements('test'); // the values from the field "test" will be used
```

#### showPasswordToggle() - show a checkbox to make the password value visible next to a password field
This method can be used to add or hide a toggle checkbox below the input field to show or hide the entered password. If the checkbox will be checked, the password entered will be displayed in plain text, otherwise not. By default, the display of the toggle checkbox is enabled.
JavaScript is used to show/hide the password in plain text.

```php
$password = new InputPassword('password');
$password->showPasswordToggle(false); // true or false
```

#### sendAttachment() - send files via the WireMail class
This method has to be used with the WireMail class. The form object has to be added as the first parameter. This method is similar to the WireMail attachment() method, but it has some extra functionality and needs to be taken instead of the attachment() method. You will find more detailled information inside the [file-upload-section](#file-uploads) later on.


```php
$m = wireMail();
$m->sendAttachment($form); // sends attachments and delete them after sending from the upload directory

$m = wireMail();
$m->sendAttachment($form, true); // sends attachments and keep them after sending from inside upload directory

$m = wireMail();
$m->sendAttachment($form, 'path/to/new/location');  sends attachments and move them after sending to a new location
```
Take a look at the [contact form](https://github.com/juergenweb/FrontendForms/blob/main/Examples/contactform.php) in the example folder which includes file upload fields.

#### allowMultiple() for file input fields
By default, file upload fields only allow to upload 1 file (single upload). With this method you can add support for multiple file uploads by adding true
or false inside the parenthesis:

**True**: renders a multiple upload field
**False**: renders a single upload field

```php
$m = wireMail();
$m->allowMultiple(true); // turns a single upload field into a multiple upload field
```
#### mailTemplate() - change/disable the usage of an email template manually
This is a new method for the WireMail class, that I have created to support the usage of stylish HTML email templates. Usually you will make this setting in the module configuration, but you can overwrite it manually on per form base if needed with this method.
Use this if you want to use an other template for sending emails or to disable the usage of a template.
If setting is set to 'none', no template will be used. Otherwise add the name of the template file.
BTW: You will find all available template files inside the [email-templates folder](https://github.com/juergenweb/FrontendForms/tree/main/email_templates).
You can also add your own email templates inside this folder.

```php
$m = wireMail();
$m->mailTemplate('template1,html'); // use template1.html for sending emails with this form
```

```php
$m = wireMail();
$m->mailTemplate('none'); // disable the usage of email templates for sending emails with this form
```

#### showClearLink() - show or hide a link to clear a file input field
By default, this feature is enabled. If you want to prevent the display of the link under a file input field, you only have to set false as parameter.
The link will only be displayed if a file was selected and added to a file upload field, otherwise it is not visible.

```php
$field->showClearLink(false); // true: link will be displayed, false: link will be removed if present
```

#### getClearLink() - get the link object described in the previous method for further manipulations
If you want to manipulate the clear link (fe adding an additional styling CSS class), you will get instance for the link with this method.

```php
$field->getClearLink(true); // returns the link object
```

#### useCharacterCounter() - add a reverse character counter below a textarea if maxLength validator is set
If you only allow a certain number of characters inside a textarea, you can show a visible character counter to the visitor, which counts the entered characters in reverse order down to zero.
The visitor will see a text like "You have 245 characters left", so he is always informed how many characters can be entered until the max limit is reached.
Please not: This method works only if the validator "lengthMax" has been added to the textarea. Without this validator, the character counter will not be displayed in any case.

```php
$field->useCharacterCounter(false); // true or false
```

#### getCharacterCounter() - get the character counter object described in the previous method for further manipulations
If you want to manipulate the character counter (fe adding an additional styling CSS class), you will get the counter object with this method.

```php
$field->geCharacterCounter(); 
```

#### addHorizontalRule() - add a hr tag to select input fields to help visually break up the options for a better user experience. 
This is a relatively new option for select input fields as described [here](https://developer.chrome.com/blog/hr-in-select).

With this method you add a horizontal rule to the options of a select to divide the content for better visibility. 

To add a horizontal rule to the options you only have insert it like this:

```php
$selectfield->addHorizontalRule(); // $selectfield is the select inputfield object
```

You can also add some attributes to the hr tag if needed:

```php
$selectfield->addHorizontalRule()->setAttribute('class', 'test'); // class "test" will be added to the hr tag
```

Here you can see how it is implemented to other options:

```php
$selectfield->addOption($this->_('Select a rating'), '');
$selectfield->addOption($this->_('Excellent'), '5');
$selectfield->addHorizontalRule()->setAttribute('class', 'test'); // hr added to the other options
$selectfield->addOption($this->_('Very Good'), '4');
$selectfield->addOption($this->_('Average'), '3');
$selectfield->addOption($this->_('Poor'), '2');
$selectfield->addOption($this->_('Terrible'), '1');
```

## Additional Markup

If you want to add some markup between the form fields you can use the markup class to add some markup to the form.
This markup object has to be added like any other form elements to the form via the add() method.

```php
$markup = new Markup();
$markup->setMarkup('<p>This is my extra markup!</p>');
$form->add($markup);
```
This class does not have other methods, like setAttribute() or others that form fields have, because it is only a simple class to add some raw HTML code to the form.

Just to mention: Beside this method you can also use the [prepend() and append() methods](#prepend-append---prepend-or-append-a-string-to-an-object-field-form-button) to add some markup to a form field. 

## Form validation
The [Valitron validation library](https://github.com/vlucas/valitron) is used to validate form values and this validation
class comes with a lot of ready-to-use validation rules.
Please take a look at [Valitron validationtypes](https://github.com/vlucas/valitron#built-in-validation-rules) for all
available validation rules. There you will all validation rules with their explanation.

In addition, I have added over 20 custom validation rules especially designed for the usage with ProcessWire, so there are
more than 60 validators available out of the box.

In addition, some of the validation rules add HTML 5 attributes to the input tag, which will be used for browser validation, if enabled.

Table of all custom validation rules for better overview:

For more detailed explanation on each validation rule click the link at the validation rule name


| Validation rule name  | Explanation                                                                                                  |
| ------------- |--------------------------------------------------------------------------------------------------------------|
| [uniqueUsername](#uniqueusername)  | Checks if a username is used by another user or not                                                          |
| [matchUsername](#matchUsername)  | Checks if a username and password match (for login)                                                          |
| [meetsPasswordConditions](#meetsPasswordConditions)  | Checks if password meets the required conditions set in the backend                                          |
| [usernameSyntax](#usernameSyntax)  | Checks if the entered username contains the allowed characters                                               |
| [uniqueEmail](#uniqueEmail)  | Checks if an email address is used by another user or not                                                    |
| [checkPasswordOfUser](#checkPasswordOfUser)  | This validation rule is for logged-in users only. Checks if entered password is the same as stored in the DB |
| [matchEmail](#matchEmail)  | Checks if a email and password match (for login)                                                             |
| [isBooleanAndTrue](#isBooleanAndTruel)  | Check if a value is from type boolean and true (no really applicable for form values)                        |
| [isBooleanAndFalse](#isBooleanAndFalse)  | Check if a value is from type boolean and false (no really applicable for form values)                       |
| [isBooleanAndFalse](#isBooleanAndFalse)  | Check if a value is from type boolean and false (no really applicable for form values)                       |
| [exactValue](#exactValue)  | Check if a value entered is exact the same value given as second parameter                                   |
| [differentValue](#differentValue)  | Check if a value entered different than the value given as second parameter                                  |
| [checkTfaCode](#checkTfaCode)  | Check if a value entered is the correct Tfa-Code sent by the TfaEmail module (only for internal usage)       |
| [differentPassword](#differentPassword)  | Checks if the password entered is different from the old password stored inside the database                 |
| [safePassword](#safePassword)  | Checks if a password entered against the blacklist of forbidden passwords                                    |
| [allowedFileSize](#safePassword)  | Checks if an uploaded file is not larger than the allowed filesize                                           |
| [noErrorOnUpload](#noErrorOnUpload)  | Checks if an error occurs during the upload of a file                                                        |
| [allowedFileExt](#allowedFileExt)  | Checks if an uploaded file is of one of the allowed extensions                                               |
| [forbiddenFileExt](#forbiddenFileExt)  | Checks if an uploaded file is of one of the forbidden extensions                                             |
| [phpIniFilesize](#phpIniFilesize)  | Checks if an uploaded file is not larger than the allowed filesize as declared in the php.ini file           |
| [week](#week)  | Checks if the entered value is in the correct format of a week. The syntax should be YYYY-Www (fe 2023-W09)  |
| [month](#month)  | Checks if the entered value is in the correct format of a month. The syntax should be YYYY-MM (fe 2023-09)   |
| [checkHex](#checkHex)  | Checks if the entered value is a valid HEX color code                                                        |
| [dateBeforeField](#datebeforefield)  | Checks if the entered date is before a given date set in another field                                       |
| [dateAfterField](#dateafterfield)  | Checks if the entered date is after a given date set in another field                                        |
| [dateWithinDaysRange](#datewithindaysrange)  | Checks if the entered date is within a given time range in days starting from a date set in another field     |
| [dateOutsideOfDaysRange](#dateoutsideofdaysrange)  | Checks if the entered date is outside a given time range in days starting from a date set in another field    |
| [firstAndLastname](#firstandlastname)  | Checks if first and lastname contains only allowed characters    |
| [uniqueFilenameInDir](#uniquefilenameindir)  | Checks if a newly uploaded file has the same filename as a file inside the destination directory  |
| [compareTexts](#compareTexts)  | Checks if a text entered in an inputfield is present in an array of texts.  |
| [checkIban](#checkiban)  | Checks if an IBAN entered in an inputfield is in the correct format.  |
| [checkBic](#checkbic)  | Checks if a BIC entered in an inputfield is in the correct format.  |
| [cyrillicname](#cyrillicname)  | Checks if a name entered in an inputfield is in the correct format of a cyrillic name.  |

Afterwards, you will find a more detailed description of all custom rules and their usage:

### uniqueUsername
Checks if a username is used by another user or not - useful for user registration form. Returns false if username is in use, otherwise false.

Parameter: validation name

```php
$field->setRule('uniqueUsername');
```

### matchUsername
This is intended to be used on login forms where you login with username and password.
Has to be added to the password field; checks if password and username matches. Returns true if username and password match, otherwise false.

First parameter: validation name / Second parameter: the field name of username field

```php
$field->setRule('matchUsername', 'myemailfieldname');
```

### meetsPasswordConditions
Has to be added to the password field; checks if password meets the required conditions set in the backend - useful for registration form.
Returns true if entered password meets the requirements, otherwise false

Parameter: validation name

```php
$field->setRule('meetsPasswordConditions');
```

### usernameSyntax
Checks if the entered username only contains a-z0-9-_.@ characters - useful for registration or profile form. Returns true if username contains only allowed characters, otherwise false.

Parameter: validation name

```php
$field->setRule('usernameSyntax');
```

### uniqueEmail
Checks if an email address is used by another user or not - useful for registration and profile form. Has to be added
to an email field. Returns true if this email is not used by another user, otherwise false.

Parameter: validation name

```php
$field->setRule('uniqueEmail');
```

### checkPasswordOfUser
This validation rule is for logged-in users only. Idea: If you want to change your password you have to enter the old password before.
And for that reason I have created this rule. So this rule is for a password field where you have to enter the current password for security reasons - useful for the profile form. Returns true if the password entered by the logged in user ist correct, otherwise false.

First parameter: validation name / Second parameter: The user object

```php
$field->setRule('checkPasswordOfUser', $user);
```

### matchEmail
This is intended to be used on login forms where you login with email and password.
Has to be added to the password field; checks if password and email matches.
It is the same validation as matchUsername, but in this case you can use email and password for the login. Returns true if email and password match, otherwise false.

First parameter: validation name / Second parameter: the field name of the email field

```php
$field->setRule('matchEmail', 'myemailfieldname');
```

### isBooleanAndTrue
You can check if a value is from type boolean and true. Returns true if value is boolean true, otherwise false.

Parameter: validation name

```php
$field->setRule('isBooleanAndTrue');
```

### isBooleanAndFalse
You can check if a value is from type boolean and false. Returns true if value is boolean false, otherwise false.

Parameter: validation name

```php
$field->setRule('isBooleanAndFalse');
```

### exactValue
You can check if a value entered inside a text field is exactly the same value as a value given. Returns true, if entered value is exactly the value of the second parameter, otherwise false.

First parameter: validation name / Second parameter: the value that the field must have

```php
$field->setRule('exactValue', 'mygivenValue');
```

### differentValue
You can check if a value entered inside a text field is different from a value given. Returns true, if entered value is different the value of the second parameter, otherwise false.

First parameter: validation name / Second parameter: the value that the field cannot have

```php
$field->setRule('differentValue', 'myvalue');
```

### checkTfaCode
This is a special method for the login process if you are using TfaEmail component. It checks if the code sent by the
TfaEmail module is correct. This validator is not intended to be for normal field validation. Returns true, if TFA code is correct, otherwise false.

### differentPassword
This validation checks if the password is different from the old password stored inside the database.
Useful if a user wants to change his password, and you have a password field for the old password and the new one.
So it compares the 2 fields that the value in the old password field is not the same as in the new one. Returns true if new password is different to the old one, otherwise false.

First parameter: validation name / Second parameter: field name of the password field

```php
$field->setRule('differentPassword' 'mypasswordfield');
```

### safePassword
This validation checks if the password is not on the blacklist, which contains the 100 most common passwords.
This validator is added to password fields by default, so no need to add it manually.
This validator is useful, if you offer a user registration on your site. Returns true, if password is not on the password blacklist, otherwise false.

Parameter: validation name

```php
$field->setRule('safePassword');
```

### allowedFileSize
This validation checks if an uploaded file is not larger than the allowed filesize. It takes the value of
$_FILES['size'] and compare it with the max file size set as second parameter.
Returns true if the uploaded file is not larger than the allowed file size, otherwise false.

First parameter: validation name / Second parameter: allowed filesize as an integer in Bytes or as a string with or without the unit in B, KB, MB, GB, TB or PB. If you do not enter a unit to a string, than the value will be interpreted automatically as bytes. 

```php
$field->setRule('allowedFileSize', 10000); // as an integer in Bytes OR
$field->setRule('allowedFileSize', '10000'); // as a string without an unit OR
$field->setRule('allowedFileSize', '10 MB'); // as a string with an unit OR
$field->setRule('allowedFileSize', 10*1024*1024); // as a calculation value
```
Please note: If you enter the value without an unit, the converted filesize in KB, MB or something else will be a calculated number.
Example: You enter 100 000 Bytes as the max filesize. This will be converted fe to 100 000/1024 = 98KB and not 100 KB. Please keep this in mind if you are using numbers without units.

### noErrorOnUpload
This validation checks if an error occurs during the upload of a file. It takes the value of
$_FILES['error'] and outputs an error message if the value is not 0. Returns true if $_FILES['error'] = 0, otherwise false.

Parameter: validation name


```php
$field->setRule('noErrorOnUpload');
```

Please note: This validator will be added to each input type file automatically. You can remove it if you want by using the removeRule('noErrorOnUpload') method.


### allowedFileExt
This validation checks if an uploaded file is of one of the allowed extensions. It takes the value of
$_FILES['name'] and extracts the extension. If the extension is not in the array of allowed extensions
an error message will be displayed. Returns true if file is type of the allowed file types, otherwise false.

First parameter: validation name / Second parameter: array of allowed file extensions

```php
$field->setRule('allowedFileExt', ['jpg','pdf','doc']);
```

### forbiddenFileExt
This validation checks if an uploaded file is of one of the forbidden extensions. It takes the value of
$_FILES['name'] and extracts the extension. If the extension is in the array of forbidden extensions
an error message will be displayed. Returns true if file is not type of the forbidden file types, otherwise false.

First parameter: validation name / Second parameter: array of forbidden file extensions

```php
$field->setRule('forbiddenFileExt', ['exe','pps']);
```

### phpIniFilesize
This validation checks if an uploaded file is not larger than the allowed filesize as declared in the php.ini file.
It takes the value of $_FILES['size'] and compare it the max file size of the php.ini file. Returns true if file is smaller than the allowed file size in php.ini file, otherwise false.

Parameter: validation name

```php
$field->setRule('phpIniFilesize');
```

Please note: This validator will be added to each input type file automatically. You can remove it if you want by using the removeRule('phpIniFilesize') method.


### week
This validation checks if the entered value is in the correct format of a week.
The syntax should be YYYY-Www. The first 4 digits are the year followed by a hyphen an a W and the week of the number. The 12th week in 2023 should be written as followed: 2023-W12. Returns true if the week is written in the correct syntax, otherwise false.

Parameter: validation name

```php
$field->setRule('week');
```

### month
This validation checks if the entered value is in the correct format of a month.
The syntax should be YYYY-MM. The first 4 digits are the year followed by a hyphen and the week of the number. The 12th month in 2023 should be written as followed: 2023-12. Returns true if the month is written in the correct syntax, otherwise false.

Parameter: validation name

```php
$field->setRule('month');
```

### checkHex
This validation checks if the entered value is a valid HEX color code.
The syntax should be #XXX or #XXXXXX. Returns true if the HEX code is in the correct syntax, otherwise false.

Parameter: validation name

```php
$field->setRule('checkHex');
```

### dateBeforeField
This is the same rule as dateBefore with the only difference that the value of the date will be taken from another form
field.

Parameter: name of the reference field that contains the date

```php
$field->setRule('dateBeforeField', 'date');
```

Explanation: The field that contains the date has the name attribute "date" in this case and is a another field inside the form.
Fe a user has entered the date 2023-05-15, this validator checks if the value entered in THIS field is before 2023-05-15.
If it is so, the validator returns true, otherwise false.
So you can check if a date entered in THIS field is before a date entered inside ANOTHER field of the form.

### dateAfterField
This is the opposition of the validator explained before.

Parameter: name of the reference field that contains the date

```php
$field->setRule('dateAfterField', 'date');
```

### dateWithinDaysRange
This is a very special validation rule, which will check if a date is within a given time range in days after another date
as set inside another field.
In other words: you have another date field inside your form, where the user enters a date (fe 2023-05-15).
Now you can enter a time range in days (fe 7 days). This time range starts from the date entered inside the other field +
7 days.
In this example the time range starts from 2023-05-15 and ends at 2023-05-22 (7 days).
This validator checks, if a date entere in THIS field is within the time range of the 7 days. If it is so, it will return
true, otherwise false.

First parameter: name of the reference field that contains the date / time range in days

A positive days value (7) means a time range in the future: from 2023-05-15 to 2023-05-22 (7 days)
A negative days value (-7) means a time range in the past:  from 2023-05-08 to 2023-05-15 (-7 days)

```php
$field->setRule('dateWithinDaysRange', 'date' 7);
```

### dateOutsideOfDaysRange
This is pretty the same validation rule as the one before, but it does not check if the date is WITHIN the time range, it 
will check if the date is OUTSIDE (before or after) the given time range.

First parameter: name of the reference field that contains the date / time range in days

```php
$field->setRule('dateOutsideOfDaysRange', 'date' 7);
```

### firstAndLastname
Checks first and lastname according to international syntax based on https://regexpattern.com/international-first-last-names/.
The regex contains only allowed characters for international names. You can use it to check first, middle or lastname or all together at once. This name check should be usable around the world. If not, let me know.

```php
$field->setRule('firstAndLastname');
```

### uniqueFilenameInDir
This validation rule checks if a newly uploaded file has the same filename as a file inside the destination directory.

In other words: If the filname of the uploaded file is textA.txt and inside the destination directory exists a file with the same name, this validator returns an error.

```php
$field->setRule('uniqueFilenameInDir');// Returns true or false
```
*Be aware*: Use this validator only if you want to store a file inside a directory/folder. Do not add it if you are only sending files as attachements via mails, because it is possible that you will get an error if the validator finds a file with the same name inside the asset folder of the page the upload form belongs too.

As an addition you can set a parameter to force an overwrite of duplicate filenames.

```php
$field->setRule('uniqueFilenameInDir', true);// Returns always true but overwrites existing filenames
```

If you add true as the second parameter, ever filename duplicate will be overwritten by adding the timestamp after the filename to make the filename unique.

Example: testfile.text will be overwritten fe to testfile-95846567.txt if it exists inside the destination directory.

In this case the validation rule returns always true.

One thing to mention: Unfortunately, Valitron does not support the output of the value of the duplicate filename, so if you are having an multiple file upload field the user will get no information about, which filename is a duplication. 

In this case it would be better to add true as second parameter and overwrite the filename.

Maybe other custom validation rules will be added in the future. If you have an idea for an useful validator, please let me know.

Inside the folder 'examples' you will find examples of the usage of validation rules inside the validationTypes.php.
Take a look at these examples on how to write and add validation rules to your input fields.
You can use as many validators to a field as you need.

### compareTexts
This validation rule checks if a text entered in an inputfield is present in an array of texts.

This validator has been created for the simple question CAPTCHA in the first instance, but it can also be used for all other text inputs too.

What it does, is to compare a text, which was entered in an input field with several texts inside an array. If one of the texts inside the array matches the entered text, than it returns true, otherwise false.

To make all texts comparable, they will be transformed to lowercase to prevent differences in upper case and lower case letters inside the comparison.

```php
$field->setRule('compareTexts', ['green','is green', 'has color green']);
```
You need to add all the texts or text snippets as an array to the validator. The value entered will be compared to these texts and returns true if a match was found, otherwise false.

### checkIban
This validator checks only if the syntax of an IBAN entered is correct, but it does not check if the IBAN really exists.

```php
$field->setRule('checkIban');
```

### checkBic
This validator checks only if the syntax of a BIC entered is correct, but it does not check if the BIC really exists.

```php
$field->setRule('checkBic');
```
### cyrillicName
This validator checks if a name entered has the correct syntax of a cyrillic name (validation rule provided by [Andy](https://processwire.com/talk/profile/2948-andy/) from the support forum).

```php
$field->setRule('cyrillicName');
```
## Create your own custom validation rules

However, this module contains many built-in validation rules, but sometimes you need a special validation rule. Fortunately, the Valitron library supports the creation of custom rules in a simple way. Please take a look at the [Valitron Library documentation](https://github.com/vlucas/valitron?tab=readme-ov-file#adding-custom-validation-rules), which explains how to create custom rules.

In ProcessWire, you can add the code for the custom rules within the site/init.php or site/ready.php. If you don't need to use values from the ProcessWire API, you can add your custom validation rules to the init.php. If you need some values from ProcessWire for validation, you'll need to put your code in the ready.php.

Here's an example that shows how to create and add a custom rule. I took the example of Valitron documentation. It should show you how to write such a custom rule.

First, add this code to your site/init.php or site/ready.php:

```php
\Valitron\Validator::addRule('alwaysFail', function($field, $value, array $params, array $fields) {
    // here you can write your validation code - this should return true or false
    return false;
}, 'is always wrong. Everything you enter is not correct. You fail.');
```

This validation rule doesn't make sense because it always returns false if you enter a value. It is for demonstration purposes only.

Here's a form example with the new custom validation rule:

```php
$form = new \FrontendForms\Form('test');

$inputText = new \FrontendForms\InputText('text');
$inputText->setLabel('Input Text');
$inputText->setRule('required');
$inputText->setRule('alwaysFail'); // here is the new validation rule
$form->add($inputText);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if ($form->isValid()) {

}

echo $form->render();
```

As you can see, you can add the new custom validation rule to any input field in the form in the same way as any other validation rule.

*Please note:* 

The validation rule must always return true or false, no other values. So please write the code of your custom validation rule so that it always returns a boolean.


## Customization of validation
For each validator rule exists an error message as a translatable string. This is ok for most cases, but
sometimes you need to show another error message than the translated one. For these cases you can customize your error
messages with 2 methods.


| Method name  | Use case | 
| ------------- | ------------- |
| [setCustomFieldName()](#setcustomfieldname)  | replace the label of the input field with a custom text in the error message  |
| [setCustomMessage()](#setcustommessage)  | overwrite the default error message completely  |


Default error message:

By default the error message uses the label of the input field and prepends it before the error message.

```php
$field = new InputCheckbox('privacy')
$field->seLabel('Privacy');
$field->seRule('required');
```
If the validation fails, the error message will look like this:

`Privacy is required`

If you do not want to use the label (in this case 'Privacy') of the field, then you can change it in the
following way:

### setCustomFieldName()
By using the setCustomFieldName() method you can replace the label of the input field with a custom text in the error message

```php
$field = new InputCheckbox('privacy')
$field->seLabel('Privacy');
$field->seRule('required')->setCustomFieldName('This field');
```
If the validation fails, the error message will look like this:

`This field is required` instead of `Privacy is required`.

### setCustomMessage()
Use this method if you want to overwrite the default error message completely (not only the label text).

```php
$field = new InputCheckbox('privacy');
$field->setLabel('Accept the privacy policy');
$field->seRule('required')->setCustomMessage('You must accept our privacy policy');
```
If the validation fails, the error message will look like this:

`You must accept our privacy policy`

## Other form elements
No only fields can be part of a form. Afterwards you will find additonal elements that can be used within your forms.

### Fieldsets
You can also add fieldsets and a legend to the form.

```php
$fieldsetStart = new FieldsetOpen();
$fieldsetStart->setLegend('My legend');
$form->add($fieldsetStart);
...
...
$fieldsetEnd = new FieldsetClose();
$form->add($fieldsetEnd);
```

### Buttons
Here is an example of a form button.

```php
$buttonReset = new Button('reset');
$buttonReset->setAttribute('type','reset');
$buttonReset->setAttribute('value', 'Reset');
$buttonReset->addWrapper()->setAttribute('class', 'myButtonWrapper');
$form->add($buttonReset);
```

### Texts
Here is an example of a text.

```php
$text = new TextElements();
$text->setContent('This is a text.');
$form->add($text);
```

BTW: You can use all text elements that are inside the Formelements/Textelements directory.

## Default fields
Writing fields is a lot of work. But it is getting more frustrating if you have to write always the same fields inside different forms.
To make life a little easier I have created the most common fields in forms as pre-defined default fields with its own class.

These are:

* Email (Input text to enter an email address - class name: Email)
* Name (Input text to enter a name - class name: Name)
* Surname (Input text to enter a surname - class name: Surname)
* Phone (Input text to enter a phone number - class name: Phone)
* Password (Input password to enter a password - class name: Password)
* Password confirmation (Input password to enter the password confirmation - class name: PasswordConfirmation)
* Privacy (Checkbox to accept the data privacy - class name: Privacy)
* PrivacyText (Text only for accepting the Terms and Privacy Policy - class name: Privacy) - this is not an input field -> it is a child of the TextElements class and can be used instead of the Privacy checkbox. It outputs only a text string
* SendCopy (Checkbox to force the sending a copy of the text of a contact form to me - class name: SendCopy)
* Subject (Input text to enter a subject - class name: Subject)
* Message (Textarea to enter a text - class name: Message)
* Gender (Select to choose the gender - class name: Gender)
* Username (Input text to enter a username - class name: Username)
* FileUploadSingle (Input file to upload a single files - class name: FileUploadSingle)
* FileUploadMultiple (Input file to upload multiple files - class name: FileUploadMultiple)
* ResetButton (Button to clear the form elements - class name: ResetButton)

Instead of creating these types of input fields every time on your own, you can use the pre-defined input types listed above instead.
Every input type has the validation rules and sanitizers included, the labels and error messages are defined set, so
you can use it as they are - but you are free to add additional sanitizer and validation rules, or you can change the
error messages to your needs.
To make this better understandable, take a look at the example below:

This is the way you will usually write an email field by hand:

```php
    $emailfield = new \FrontendForms\InputfieldText('myemailfield');
    $emailfield->setLabel('Email');
    $emailfield->setRule('required');
```

There is nothing wrong with it, but by using the Email() class, you only have to write one line instead of multiple.
So this will be a real time saver to you and keeps the code inside your template as short as possible.
Another advantage is that the you do not have to set label and other texts manually, because they are set inside the constructor of the class.

```php
    $emailfield = new \FrontendForms\Email('myemailfield');
```

You can do the same with all others mentioned pre-defined input types. You only have to instantiate the class of the
input type and add the field to the form.

BTW you will find the files of all pre-defined input types inside the "defaults" folders of each input type.

- [Formelements/Inputelements/Inputs/defaults/](https://github.com/juergenweb/FrontendForms/tree/main/Formelements/Inputelements/Inputs/defaults)
- [Formelements/Inputelements/Select/defaults/](https://github.com/juergenweb/FrontendForms/tree/main/Formelements/Inputelements/Select/defaults)
- [Formelements/Inputelements/Textarea/defaults/](https://github.com/juergenweb/FrontendForms/tree/main/Formelements/Inputelements/Textarea/defaults)
- [Formelements/Inputelements/Miscellaneous/defaults/](https://github.com/juergenweb/FrontendForms/tree/main/Formelements/Miscellaneous/defaults)


You can study the code to see, what validators and sanitizers are included by default. If you have an idea for another
inputfield, please let me know.

Contact form 2 inside the examples folder uses these pre-defined input types. [Take a look](https://github.com/juergenweb/FrontendForms/blob/main/Examples/contactform-2.php) at it to see how you can use pre-defined input types to make your life easier.

## File uploads
Uploading files is sometimes needed, so this module supports uploading files for storing it inside the site/assets/files
folder or to send it via email.

### Upload a file for storing it under site/assets/files
The site/assets/files ist the Processwire directory where all the file will be stored. This directory is public
reachable, so that the files could be fetched via fe a link.
If you want to upload a file under this directory you have to do nothing, because this is the default behavior.
Add only a file upload field to your form, and you are done. Each uploaded file will be stored under
site/assets/files/ and the number of the page, where the form belongs to (fe site/assets/files/1094).
If you want to store the file at another location you can change the target folder by using the setUploadPath() method.
You will find more information about the setUploadPath() method [here](#setUploadPath).

If you want to see a real world example, please take a look at the example page at [site/modules/FrontendForms/Examples/fileuploadtopage.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/fileuploadtopage.php) inside the Examples folder.

### Upload a file for sending it with an email
In this case you have to add the sendAttachements() method to the WireMail object. Otherwise, the files will not be sent
with the email. You will find more information about the sendAttachements() method [here](#sendattachment-for-file-input-fields).
This method takes the file from the location where it was stored after the upload and adds it to the mail.

```php
$mail->sendAttachements($form);
```

As you can see, you have to set the form object as parameter inside the brackets. This is necessary otherwise it will
work. This is all you have to do to send your files as mail attachments.

By default, the files will be removed from the server after the mail have been sent.

This method offers you 2 additional possibilities by adding a second parameter to the method:

* do not delete the file after sending and
* move the file to another location after sending

To keep the file after sending, you only have to add boolean true as the second parameter:

```php
$mail->sendAttachements($form, true);
```
In this case, the files will be sent, but will be also stored inside a folder that was defined with the [setUploadPath()](https://github.com/juergenweb/FrontendForms/tree/main#setuploadpath---change-the-default-storage-location-for-uploaded-files) method. There you can set a path to a folder.

If the upload path is not set explicitely, the folder of the page where the form is integrated will be taken instead.

Example:

If the upload form is integrated inside the page with the id 10, then the files will stored inside site/assets/files/10/

If you have defined a custom upload path (fe site/assets/files/customfolder) like this 

```php
$form->setUploadPath('site/assets/files/customfolder);
```
then all files will be stored inside this folder.

To move files after sending to a new location, you have to enter the path to the new location as second parameter:

If you want to see a working real world example of sending attachments, please take a look at the example page at
[site/modules/FrontendForms/Examples/contactform.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/contactform.php)
inside the Examples folder.


## Hooking
Hooking is not really necessary in most cases, because you have so much configuration options and public methods to achieve your desired
result. Anyway, if there is a need for it, every method with 3 underscores is hookable.

### Hook example 1: Change the asterisk markup via a Hook
If you are not satisfied with the markup for the asterisk on required fields, you can use the following Hook inside
your init.php to create your own markup.

Before:
```html
<span class="asterisk">*</span>
```
Hook function

```php
$wire->addHookAfter('Label::renderAsterisk', function(HookEvent $event) {
  $event->return = '<span class="myAsterisk">+</span>';
});
```
After:
```html
<span class="myAsterisk">+</span>
```

### Hook example 2: Add Font Awesome exclamation sign in front of the error message

Before:
```html
<p class="uk-text-error">This is the error message.</p>
```
Hook function

```php
$wire->addHookAfter('Errormessage::render',
function(HookEvent $event) {
    $msg = $event->object;
    $errorText = $msg->getText();
    if($errorText){
        $fontAwesome = '&#9888; ';
        $event->return = $fontAwesome.$errorText;
    }
});
```
After:
```html
<p class="uk-text-error"><i class="fas fa-exclamation-triangle"></i>This is the error message.</p>
```

### Hook example 3: Add Font Awesome exclamation sign in front of the input field description

Before:
```html
This is my input Description.
```
Hook function

```php
$wire->addHookAfter('Description::render', function(HookEvent $event) {
    $desc = $event->object;
    $descText = $desc->getText();
    if($descText){
        $fontAwesome = '<i class="fa fa-warning"></i>';
        $event->return = $fontAwesome.$descText;
    }
});
```
After:
```html
<i class="fas fa-exclamation-triangle"></i>This is my input Description.
```

### Hook example 4: Add Font Awesome info sign in front of the input field notes

Before:
```html
This is my input notes text.
```
Hook function

```php
$wire->addHookAfter('Notes::render', function(HookEvent $event) {
    $notes = $event->object;
    $notesText = $notes->getText();
    if($notesText){
        $fontAwesome = '<i class="fa fa-info"></i>';
        $event->return = $fontAwesome.$notesText;
    }
});
```
After:
```html
<i class="fa fa-info"></i>This is my input notes text.
```

I guess you get the idea how to customize your output a little bit by using hooks.

## Multi-language
This module supports multi-language. All text strings are fully translatable in the backend.
The default language is English.

### Using translation files from languages folder
If you are using ProcessWire version 3.0.181 or higher, you can take advantage of the new feature of using CSV files
for different languages. All other versions below do not support this feature.
The folder languages includes translation files for the following languages at the moment:

* German

Maybe other language files will be added in the future.

#### How to install the module language files in ProcessWire
After you have installed the module go to the configuration page of the module.
There you will find inside the module info tab a new item called "Languages". Beside this there will be a link to
install the existing language files. Click the link, choose the correct file for your language and press the save button.
Now the language files shipped with this module are installed, and you did not have to translate the strings by yourself.
Only to mention: If the language files will be updated, you have to install them once more. They will not be updated
automatically.

## Email templates
In most cases forms are used to send data via emails (fe a simple contact form).
ProcessWire is shipped with the WireMail class to send emails.
Unfortunately this class does not support the usage of stylish HTML email templates by default, so I decided to enhance
this class with a new method to simply choose an email template, which is stored inside the [email_templates folder](https://github.com/juergenweb/FrontendForms/tree/main/email_templates) of this module.

This module contains some email templates that you can use to send stylish HTML emails. In the backend configuration, you will find a setting where you can select your preferred HTML template globally. This is done with an easy-to-use image picker.

![Template select](https://github.com/juergenweb/FrontendForms/blob/main/images/template-select.png?raw=true)


## Custom email templates
If you want to use your own custom email templates, an extra folder called **"frontendforms-custom-templates"** will be created during the install process under the site directory. So you will find this folder here: site/frontendforms-custom-template/.

Please put all your own custom email templates inside this folder and not in the default email templates folder as described in the previous point. Otherwise your custom email templates will get lost after an update.

If you want to display a thumbnail of your custom template in the template selector, as mentioned in the previous section, you'll need to create the thumbnail of your template yourself and place it next to the custom template file in the **frontendforms-custom-templates** folder.

*Please note:* To properly display the template's thumbnail, you need to save it in PNG format and in the size of 200x200px.

If you want to use another folder than the mentioned folder **"frontendforms-custom-templates"**, you can also create this folder manually and put all your custom templates inside. 

In this case you have to add the complete path to the mail templates to the mailTemplate() method (see the next point).

**Just to mention:** In this case, your custom templates will not be displayed inside the template selector in the backend.

To create your own custom templates, I recommend you to study the templates inside the email templates folder and adapt them to your needs.
The custom email templates folder will be created during the install process automatically. If it is not there, please create it manually.

### New method mailTemplate()
First you need to know, that inside the email_templates folder you will find HTML files with various names
(fe template_1.html, template_2.html,...).
These files are the one you can use as the parameter within the brackets of this method:
You can find an input select containing all existing email templates files inside the module configuration page too, where you can set an email template globally.

If you want to overwrite the global setting for the template on per form base you have to use this mailTemplate() method. Otherwise, the global settings will be used. So, the usage of this method is optional.

```php
$mail = new WireMail();
$mail->mailTemplate('template_1.html'); // this adds the template with the file template_1.html to the email
```

If you have stored your custom mail templates inside a custom folder as written in the point before you have to add the complete path to the file as the method parameter:

```php
$mail = new WireMail();
$mail->mailTemplate($config->paths->site.'assets/mycustomfolder/mytemplate.html'); // this adds the template under the given path to the mail
```

Every template file contains placeholders for your content. You will find more information about placeholders [here](#placeholder-variables-for-usage-in-email-templates).
F.e. the text of the subject will be rendered inside the placeholder [[SUBJECTVALUE]],
the text of the body inside the placeholder [[BODY]].
I have decided to use the double square brackets syntax, because this syntax is also used in the Hanna code module.
It is recommended to take a look at the email templates which are shipped with this module. You can take them as an
example on how to write your own email templates (or download free templates from the internet and add the placeholders
by yourself).

If you have created a template on your own, add it to the the custom folder **"frontendforms-custom-template"** or inside another folder that you have created and not inside the "email_templates"!!
Now you can go to the module configuration and under the email settings tab you can select your custom email template.

**Just note:**

Templates, which are not stored inside the **"frontendforms-custom-template"** or the **"email_templates"** folder cannot be selected as the global template inside module configuration. 

### Images inside email templates
You can also use images in your email templates, but be aware to use the absolute URL to this image (not relative).
Template file "template_2.html" uses the ProcessWire logo with an absolute URL from the internet. You can take a look
at this template file how to include images in email templates.

#### Storage place for images for email templates
This module creates a new folder inside the site/assets/files folder of ProcessWire called FrontendForms during the module
installation. This folder is intended to be used for images that you want to use within the email templates. This folder has public readability and can be reached from outside. This is absolutely necessary to be able to view images inside the emails!!!

Before the module will be installed, there is also a folder called assets inside this module. It contains images for the
ready to use email templates.

During the installation process, these images will be copied from the assets folder inside the module to the newly
created FrontedForms directory. The assets folder inside the module directory will be deleted afterwards.

If you uninstall the module, the assets folder will be copied back to the module folder. Afterwards the FrontendForms
folder will be removed from the site/assets directory.

If you are creating your own email template, please put the images inside the FrontendForms
folder. It is recommended to use a separate folder for each template (fe. site/assets/FrontendForms/mytemplateFolder).

You can find an example of using a local stored image inside the ["template_3.html"](https://github.com/juergenweb/FrontendForms/blob/main/email_templates/template_3.html#L365) file. So take a look there how to accomplish this.
Please note: If you are running your site on a local server (XAMPP, WAMPP,..), local stored images will not be displayed
inside the emails because they are locally and therefore not reachable via the internet.

After you have transferred your site to a live server, the images should be displayed properly inside the emails.

### Placeholder variables for usage in email templates
Placeholder variables are variables that can be integrated easily in the email templates. Their purpose is to add some
data to the email templates without using php code. They can also be used for the body text of the email (you will find
a real world example in the contactform.php inside the [examples folder](https://github.com/juergenweb/FrontendForms/blob/main/Examples/contactform.php)).
Each placeholder variable is surrounded by 2 square brackets (fe. [[DATEVALUE]]) and has to be written in
uppercase letters.
This variable will be replaced on the fly by the appropriate value, provided by a php code.
For example: If you want to add the current date to a template, you only have to write [[CURRENTDATEVALUE]] and this placeholder
will be replaced by the current date.

There are a lot more placeholder variables: You can take a look if you are using the method getMailPlaceholders() on your form
object. You will get an array of all available placeholders, that can be used.

```php
$form = getMailPlaceholders(); // this will output an array of all global placeholders that can be used inside your templates
```

In addition, a placeholder for each field of the form will be created automatically.
Assume you have an input field in your form with the name/id attribute of this field "myname".
2 Placeholder will be created automatically for the label and the value of the field and both can
be used inside the email template or the email body text.

Take a look at the example form below:

```php
$namefield = new \FrontendForms\InputfieldText('myname');
$namefield->setLabel('My name');
```

As you can see, the inputfield has the name/id "myname."
The placeholders, that will be created automatically are:

[[MYNAMELABEL]] and [[MYNAMEVALUE]]

So it takes the name/id attribute of the form field and adds LABEL for the label value and VALUE for the value itself.
You can use both of them inside the body variable of your form. Both placeholders will be replaced by their appropriate
values before the email has been sent.

So beside the global placeholders you can use also placeholder values of fields inside the form. The usage of placeholders is a nice way to add data to a template without using PHP code, so this method keeps your template clean from code.

You can also set placeholders to the mail->body().

```php
$mail->body(_('These are the form values: [[SURNAMELABEL]]: [[SURNAMEVALUE]], [[NAMELABEL]]: [[NAMEVALUE]]'));
```

So there will be no need to include the values with php code, but you can do it, if you want.

#### Adding custom placeholders for usage in templates
If you think about creating or using a custom HTML email template, and you want to use a special placeholder there
(fe the name of your company), you can add a custom placeholder to your form with the following method.

```php
$form->setMailPlaceholder('companyname', 'My company');
```
Then you will be able to use the custom placeholder inside your email template: [[COMPANYNAME]].
Before sending of the email the placeholder will be replaced by "My company".

BTW, adding the company name via a placeholder is not really a good example, because the company name is always
the same, and therefore it can be added hardcoded to the template (no need for creating a placeholder).
A better example would be fe an order number, because this number is variable.

```php
$form->setMailPlaceholder('ordernumber', '123456');
```
By using a placeholder variable, there is no need to add php code to the email template to grab the order number.
This makes the email template code much more cleaner.

#### New method title()

The title method adds a title, also known as pre-header attribute to the HTML template which will be displayed under the subject.
This will be added automatically to the email, independent if you are using a HTML email template or not - no need to add it manually.

This value will be placed inside an div tag and will be invisible to the user.

BTW, if you want to show this value inside your template, you can use the placeholder [[TITLE]] inside the body area and the value will be visible for the user.


```php
$mail = new WireMail();
$mail->title('This is my title');
```

## Use forms in iframe

If you want to use a form inside an iframe, it works without problems if the form and the iframe are on the same domain.

If you try to embed a form inside an iframe on a different domain, you will run into session problems, because the form submission uses various sessions to be able to work properly.

In this rare case scenario, you will need to add an additional method to the form object to disable all form methods wich use session. This method is called useFormInCrossDomainIframe():

```php
$form->useFormInCrossDomainIframe();
```

The disadvantage of this method is that the usage of a CAPTCHA is no longer possible, because it only works with session.
CSRF-Protection and the check for double form subission are also disabled, but this is the only way to use a form inside an iframe on another domain.

## Custom module FrontendFormsManager

### What is the module FrontendFormsManager?
FrontendFormsManger is a module that is delivered with FrontendForms, but is not installed by default, as it is not necessary for the operation of FrontendForms. 

### What can I do with this module?

1. This module is for entering questions for the SIMPLE QUESTION CAPTCHA in an easy-to-use interface and to make these questions usable in all forms. So you can manage all your questions in one place and have the possibility to add, edit or delete questions or even publish or not publish them. It also includes a small statistical section with charts.

2. You can manage supicious IP adresses. These are IPs that have been temporary blocked after too many attempts to submit a form. FrontendFormsManager offers a little statistic section, where you can identify potential spammers by checking the number of blockings of a certain IP. Then you can decide if you want to add this IP to a blacklist or not. Any IPs on this blacklist cannot submit forms any longer, because the forms will not be displayed in this case.

### How does it work?

Technically spoken, this module creates a new admin page under SETUP called *FrontendForms Manager*.

![Navigation](https://github.com/juergenweb/FrontendForms/blob/main/images/setupmenupoint.png?raw=true)

After clicking on the navigation link you will be redirected to the dashboard page, which contains the overview. 

![Dashboard page](https://github.com/juergenweb/FrontendForms/blob/main/images/dashboard.png?raw=true)

If you click on the "Add/edit/delete questions" button, you will be redirected to the next page, which contains a list of all questions. This list is a data table with a small filter form that you can use to filter the questions according to your needs (e.g. status, question text, visibility in the frontend).

![Data table with questions](https://github.com/juergenweb/FrontendForms/blob/main/images/questiontable.png?raw=true)

Note: The table displays 10 questions per page by default. If the number of questions is higher, the table will be paginated. You can change the number of questions/page in the module configuration according to your needs.

Each question is a page, so you can do everything you can do with all the other pages in ProcessWire. All fields and templates for these pages are created automatically during the module installation.

If you want to edit or delete a page, click directly on the linked question text in the table and you will be redirected to the editing page for this question. If you click on "Add new question", you will be redirected to the "Add new page" page.

If you have no idea for simple questions, you will find some example questions [here](https://github.com/juergenweb/FrontendForms/blob/main/Examples/examplequestions.txt).

The next image shows the edit page of a question.

![Edit page](https://github.com/juergenweb/FrontendForms/blob/main/images/questionpage.png?raw=true)

In addition to the mandatory fields "Question" and "Answers", there are a number of other optional fields that you can fill in, e.g. a description text, a note text, a customized error and success message etc., but these fields are optional and therefore do not have to be filled in. You can use these fields for further customization of a single question.

The next image shows the CAPTCHA input field.

![Captcha input field](https://github.com/juergenweb/FrontendForms/blob/main/images/captchafield.png?raw=true)

You can also find all question pages in the page tree under the administration page.

![Page tree](https://github.com/juergenweb/FrontendForms/blob/main/images/pagetree.png?raw=true)

You need to enter at least 1 question to get it working. If you have installed the module, but no questions have been entered, than the single question from the module configuration will be taken as the fallback instead. In this case you have only a "single question CAPTCHA" instead of a "multi question CAPTCHA";

## How to install/uninstall the FrontendFormsManager module

This is a normal ProcessWire module, so the installation/uninstallation of the module is the same as for all other modules. Go to the module area, select the "FrontendForms Manager" module and install it as usual.

After installing the module, you will find the new navigation link *FrontendForms Manger*, as described above, under the navigation item SETUP.

If you want to uninstall the module, go to the module section and click to uninstall the module.

![Module installation](https://github.com/juergenweb/FrontendForms/blob/main/images/installmodule.png?raw=true)

NOTE: Uninstalling the module also deletes all pages, fields and templates that were created by this module. This means that all questions will be deleted and will no longer be available after uninstalling of the module. Please keep this in mind when uninstalling the module!

## Inputfield dependencies

Inputfield dependencies is a JavaScript implementation that allows you to show/hide form elements (inputfields, alerts, texts, etc.) or to enable/disable inputfields according to the value(s) set inside one or more other fields with pre-defined values such as checkboxes, radios and selects.

Writing such a script is very complex and difficult, so I have not written this script by myself.
It is a script written by Ali Khallad, but I found it very useful, so I decided to implement it into FrontendForms.
You will find the script and the original documentation at https://github.com/bomsn/mf-conditional-fields.
I can recommend that you not only read my documentation here, but also take a look at the documentation there.

**Please note**: 

Writing conditions seems to be not so easy to understand at the first sight, but it is very easy, believe me.

For better understanding, I have written a lot of examples on how to write conditions. You will find them all inside the [Example folder](https://github.com/juergenweb/FrontendForms/tree/main/Examples/inputfield_conditions).
Please study these examples to get an idea of how it works. 
 
### What is the use case of using input field dependencies?

Let me explain the usage according to an example:

Let's say you have two fields in your form: a number input field (field 1) and a text input field (field 2).

Field 2 should only be visible if the value "1" is selected inside field 1. Otherwise, field 2 should be hidden.

The input field dependencies allow you to add the condition directly to field 2 without having to write a line of JavaScript.

Here is an example on how to add such a condition to an input field:

```php
$field2->showIf([
        'name' => 'field1', 
        'operator' => 'is',
        'value' => '1'
    ]);
```

This rule in words: Show field 2 only if value 1 is selected in field 1. Otherwise hide it.

That is all and now field 2 will be only visible, if you select value 1 on field 1.

### On which fields can I add a dependency?

You can add a dependency on every form element (button, input field, alert,..). There is no restriction, but the most used case is to show/hide an input field.

Disable/enable functionality is for input fields only.

### Which fields can be used as reference fields?

A reference field is a field from which you retrieve the comparison value for the condition. 

Only fields with pre-defined values can be used as reference field. Therefore you can use only the following input types:

* checkbox single
* checkbox multiple
* radio single
* radio multiple
* select single
* select multiple 

Other input field types are not supported as a reference field (eg a text input).

### Writing a rule

Dependency rules have to be written as an array with the keys "name", "operator" and "value".

```php
$rule = ['name' => 'field1', 'operator' => 'is', 'value' => '3'];
```

* The name value should be the name attribute of the reference field (in this case field1)
* The operator should be the comparison type (is, isnot, largerthan, beginswith,...). these types will be explained later on
* The value should be the value of the reference field, which should trigger the action (hide, show,...) 

### 4 types of conditions

As mentioned before, you can show, hide, enable or disable form elements. For this reason, you have 4 methods, that have to be added to a field:

* showIf()
* hideIf()
* enableIf()
* disableIf()

```php
$field->showIf([rule]);
```
You will find an example of each type at [Examples/inputfield_conditions/conditiontypes.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/inputfield_conditions/conditiontypes.php). Please take a look there on how to use them.

### 2 types of logic: AND / OR

The logic operator is only relevant if you are combining 2 or more rules. The OR-operator is set by default. So if you want to combine multiple rules
via OR-logic, you do not have to do anything.

If you want to combine them via AND-logic, you have to add the logic after the rules as second parameter.

```php
$field->showIf([rules], 'and');
```

I have also written some examples using the AND and OR logic and you will find them at [Examples/inputfield_conditions/multipleconditionsWithAndOr.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/inputfield_conditions/multipleconditionsWithAndOr.php). 

### 12 types of operators

Operators are for comparing a given value with a selected value (<, = <= and so on).

At the moment, 12 types of operators will be supported:


* is (equals: =)
* isnot (equals: !=)
* greaterthan (equals: >)
* lessthan (equals: <)
* contains (equals: %=)
* doesnotcontain (equals: %!=)
* beginswith (equals: *%=)
* doesnotbeginwith (equals: *%!=)
* endswith (equals: %*=)
* doesnotendwith (equals: %*!=)
* isempty (equals: ='')
* isnotempty (equals: !='')

The names of the operators are self describing, so I do not explain them here (I guess you know for what they are for).

For an example of each operator, please take a look at [Examples\inputfield_conditions\operatortypes.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/inputfield_conditions/operatortypes.php).

### Using inputfield dependencies on multivalue inputfields

Checkboxes and Selects allow you to select multiple values instead of only one if they have the attribute multiple. For this reason, they have to be treated
a little bit different than the rest.

The biggest difference is by checking for multiple values, and each of the values has to be selected.

Let's say you have 3 checkboxes with the values 1, 2, and 3 and you want to check if 2 AND 3 are checked, you have to write
the values separated by a "|"

```php
value = '2|3'
```

The best way to understand how it should be written is to take a look at the examples at
[Examples/inputfield_conditions/multivaluefieldsconditions.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/inputfield_conditions/multivaluefieldsconditions.php). There, you will find an example for each use case.

### Combining multiple rules 

If you want to add more than 1 rule to an element, you can write multiple rules separated by a comma inside an array:

Single rule: 

Syntax: [condition]

```php
$rule = ['name' => 'field1', 'operator' => 'is', 'value' => '3'];
```

Multiple rules: 

Syntax: [[condition 1], [conditon 2]]

```php
$rules = [['name' => 'field1', 'operator' => 'is', 'value' => '3'], ['name' => 'field3', 'operator' => 'isnot', 'value' => '5']];
```

Take a look at the examples at [Examples/inputfield_conditions/multipleconditionsWithAndOr.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/inputfield_conditions/multipleconditionsWithAndOr.php) for a better understanding.

### Show/hide elements that are not input fields

As written before, you can also show/hide other form elements, not only input fields. You will find examples of showing/hiding text elements, alert elements, and so on 
at [Examples/inputfield_conditions/usingconditionsOnNonInputfields.php](https://github.com/juergenweb/FrontendForms/blob/main/Examples/inputfield_conditions/usingconditionsOnNonInputfields.php).

### Limitations

This JavaScript feature does not cover all possible use cases, but it allows you to perform standard comparison operations.
If you need a special comparison you have to write your own JavaScript snippet or you write a feature request to the author
of this script on Github (https://github.com/bomsn/mf-conditional-fields).  

## Adding forms via placeholders to CKEditor fields

Sometimes it can be useful to insert forms directly between text stored in CKEditor fields. This is now possible
by using placeholders inside these fields, which are replaced by forms during the rendering process.

> [!NOTE]
> Thanks to Donatas ([github/donatasben](https://github.com/donatasben)), who uses FrontendForms for his projects and has the idea and the need to implement such a feature for a project.
He offers me his code for a working solution, and I have implemented it into FrontendForms.
So, this new feature is made available to you by Donatas.

### Syntax for the placeholder

You need to write the placeholder in your CKEditor field like this:

```
{{nameofyourplaceholder}}
```

Enter the name of your placeholder between 2 opening and closing brackets. It does not matter if you write the name of the placeholder in lower case only, uppercase only or mixed letters. The syntax is not case sensitive. You only have to take care, that there is no empty space between the placeholder name and the brackets. 

### How does it work?

To display a form in a CKEditor field, you must include the full code for the form in a function inside the template.

The name of the function is also the name of the placeholder and the ID of the form. 
The only parameter you need to add to the function is the variable $id. This variable will be replaced later on with the dynamic ID for the form and cannot be changed manually.

Instead of echoing the form, you have to set a return command at the end.

Here you can see an example of a simple form wrapped inside a function. You can copy this code to a template to see how it works in real life if you want.


```php

// Function name is formInterests, so the placeholder name is "formInterests" or "forminterests" or "FORMINTERESTS".
// You can write the placeholder as you want (uppercase, lowercase, mixed).
// The id of the form is also "forminterests" + the suffix containing the number of the form (eg forminterests__1).
// The dynamic ID will be set automatically.

function formInterests($id){ // add the variable $id without a value as the functions's parameter

   $form = new \FrontendForms\Form($id); // use $id from the function parameter

   $php = new \FrontendForms\Select('php');
   $php->setLabel('My preferred PHP version is');
   $php->setDefaultValue('PHP 8');
   $php->addOption('PHP 6', 'PHP 6');
   $php->addOption('PHP 7', 'PHP 7');
   $php->addOption('PHP 8', 'PHP 8');
   $php->setRule('required');
   $form->add($php);

   $button = new \FrontendForms\Button('submit');
   $button->setAttribute('value', 'Send');
   $form->add($button);

   if ($form->isValid()) {
      // do whatever you want with the POST values
   }

   return $form->render(); // do not echo the output -> use return instead
}
``` 


You can name the function whatever you like as long as it is unique inside the template. You can name it for example "contactForm" or "newsletter".
In this case you need to use the placeholders "contactForm" or "newsletter" inside your CKEditor fields (using mixed, upper or lowercase letters).

At the end of the function, the form object will be returned with the return command (no echo command).

Best of all, you can add multiple instances of the same form to the editor panel without having to worry about the form IDs.
Take a look at the following usage.

![Placeholder in backend](https://github.com/juergenweb/FrontendForms/blob/main/images/forminterests-backend.jpg?raw=true)

You can see, that the placeholder "forminterests" is added 2 times to the CKEditor field "body" and 1 time to CKEditor field "sidebar".

Every placeholder of the same type will be replaced with the same type of form, but every form has a unique ID.
This is accomplished by adding an increment number to the end of the form ID (e.g. contactform__1, contactform__2 and so on) automatically, 
so you do not have to take care about it!

This is how it looks like on the frontend:

![Placeholder in backend](https://github.com/juergenweb/FrontendForms/blob/main/images/forminterests-frontend.jpg?raw=true)

### Limitations 

You can only add forms via placeholders that are set via the function within the template of the displayed page. You can't view forms that are in another template.
For example, you have a contact page, and there you have created the contact form inside a function. It is not possible
to view this form on another page with another template (e.g. About us page using the about-us template). It can only be
displayed on the contact page in this case.

Placeholders can only be used in CKEditor fields, not in other fields like Textarea.
