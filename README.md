# FrontendForms
A module for ProcessWire to create and validate forms on the frontend easily using the [Valitron](https://github.com/vlucas/valitron) library.

## Highlights
1. Simple form creation
2. 40+ validation types
3. Support for UiKit 3 and Bootstrap 5 CSS framework
4. SPAM protection
5. Highly customizable
6. Hookable methods for further customization
7. Multi-language
8. Usage of HTML email templates possible if you are sending emails with your forms

## Table of contents
* [Quick-start guide](#quick-start-guide)
* [Which validation rules are ready to use](#which-validation-rules-are-ready-to-use)
* [Which input types are supported?](#which-input-types-are-supported)
* [SPAM protection](#spam-protection)
* [Prevent double form submission](#prevent-double-form-submission)
* [Module configuration settings](#module-configuration-settings)
* [Support for UiKit 3 and Boostrap 5](#support-for-uikit-3-and-boostrap-5)
* [General methods](#general-methods)
* [Form and its methods](#form-and-its-methods)
* [Input fields and their methods](#input-fields-and-their-methods)
* [Customization of validation](#customization-of-validation)
* [Fieldsets](#fieldsets)
* [Buttons](#buttons)
* [Default fields](#default-fields)
* [File uploads](#file-uploads)
* [Hooking](#hooking)
* [Multi-language](#multi-language)
* [HTML email templates](#email-templates)

## Quick-start guide
1. Download and extract FrontendForms and put the folder inside site/modules. Be aware that the folder name must be 
2. FrontendForms.
3. Login to your admin area and refresh all modules.
4. Now you can grab this module and install it.
5. After the installation is finished you can change some configuration settings if you want, but for starting it is not
6. really necessary.
7. Include the module in your project by putting these 2 lines of code fe inside your _init.php inside your template 
8. folder, so you can use FrontendForms across your site. This works only if you set 
9. $config->prependTemplateFile = '_init.php' inside your /site/config.php.
By the way you can include these 2 lines also inside the template file where you want to create your form, but it is 
10. not the recommended way.

```php
$frontendforms = new FrontendForms();
$frontendforms->setLang('de');
```

Line 1 loads the module with all its classes and on line 2 (optional) you can define a language (default is English) for
the pre-defined error messages.
Please take a look inside the folder lang of this module which languages are available. These files contain pre-defined 
error messages in each language for the different validation types, but you can also set your custom error messages if 
you want (read more at the customization part later on).
Now you are ready to use the module in any template file.

6. Copy the following code and paste it in a template of your choice

Be aware of namespaces!! If you are using a namespace on the top of your template file, you have to adapt the 
instantiation of the class by using the FrontendForms namespace. 
This module runs in its own namespace called FrontendForms. Take a look at the following example:

```php

// if you are not using a namespace on your template file, instantiating a class without using the FrontendForms
// namespace will be fine
$form = new Form('myForm'); // usage with custom ID inside the constructor

// but if you are using a namespace you have to add the FrontendForms namespace in front of your class name
$form = new \FrontendForms\Form('myForm');
// you have to do it on every class instantiation (fe input field, select field,..) not only on the Form class as in
// the example above
```

```php
$form = new Form('myForm');

$gender = new Select('gender');
$gender->setLabel('Gender');
$gender->addOption('Mister', 'Mister');
$gender->addOption('Miss', 'Miss');
$form->add($gender);

$surname = new InputText('surname');
$surname->setLabel('Surname');
$surname->setRule('required');
$form->add($surname);

$name = new InputText('lastname');
$name->setLabel('Last Name');
$name->setRule('required');
$form->add($name);

$email = new InputText('email');
$email->setLabel('E-Mail');
$email->setRule('required');
$email->setRule('email');
$form->add($email);

$subject = new InputText('subject');
$subject->setLabel('Subject');
$subject->setRule('required');
$form->add($subject);

$message = new Textarea('message');
$message->setLabel('Message');
$message->setRule('required');
$form->add($message);

$privacy = new InputCheckbox('privacy');
$privacy->setLabel('I accept the privacy policy');
$privacy->setRule('required')->setCustomMessage('You have to accept our privacy policy');
$form->add($privacy);

$button = new Button('submit');
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
* As you can see first you have to create a new form object. Inside the constructor you have to add the id of the form.
* After that you have to add each form field to the form. Each form field must have a name attribute inside the 
* constructor (required).
* You can set various properties to each form field (setLabel(), setNotes(), setDescription();setRule(), setSanitizer(),...)
* Use the add method to add the field to the form object.
* The isValid() method returns true or false, and you can use it to send fe the values as an email or save values to the
* database, to login a user,....you got the idea. The validation and sanitization of the form values happens inside this method.
* The render method outputs the markup of the form.

I highly recommend you to study the examples inside the 'examples' folder. There you will find a lot of different use 
cases.

Now you are ready to test the module inside your project!

## Which validation rules are ready to use
Please take a look at [Valitron validationtypes](https://github.com/vlucas/valitron#built-in-validation-rules) for all 
available validation rules. There you will find the explanation to each validation rule.
In addition, I have added 13 custom validation rules especially for ProcessWire:

* **uniqueUsername**\
Checks if a username is used by another user or not - useful for user registration form.

* **matchUsername**\
Has to be added to the password field; checks if password and username matches - useful for login form.

* **meetsPasswordConditions**\
Has to be added to the password field; checks if password meets the required conditions set in the backend - useful for registration form.

* **usernameSyntax**\
Checks if the entered username only contains a-z0-9-_. characters - useful for registration or profile form.

* **uniqueEmail**\
Checks if an email address is used by another user or not - useful for registration and profile form.

* **checkPasswordOfUser**\
This validation rule is for logged-in users only. Idea: If you want to change your password you have to enter the old password before.
And for that reason I have created this rule. So this rule is for a password field where you have to enter the current password for security reasons - useful for the profile form.

* **matchEmail**\
Has to be added to the password field; checks if password and email matches - useful for login form.
It is the same validation as matchUsername, but in this case you can use email and password for the login.

* **isBooleanAndTrue**\
You can check if a value is from type boolean and true.

* **isBooleanAndFalse**\
You can check if a value is from type boolean and false.

* **exactValue**\
You can check if a value entered inside a text field is exactly the same value as a value given.

* **differentValue**\
You can check if a value entered inside a text field is different from a value given.

* **checkTfaCode**\
This is a special method for the login process if you are using TfaEmail component. It checks if the code sent by the 
TfaEmail module is correct.

* **differentPassword**\
This validation checks if the password is different from the old password stored inside the database. 
Useful if a user wants to change his password.

* **safePassword**\
  This validation checks if the password is not on the blacklist, which contains the 100 most common passwords.
  This validator is added to password fields by default, so no need to add it manually.
  This validator is useful, if you offer a user registration on your site.

Maybe other custom validation rules will be added in the future. If you have some ideas, please write a pull request.

Inside the folder 'examples' you will find examples of the usage of validation rules inside the validationTypes.php.
Take a look at these examples on how to write and add validation rules to your input fields. 
You can use as much validators for a field as you need.

### Custom validation rules
It is also possible to create your own custom validation rules and use them inside your forms. On the Valitron GitHub page you can find more information about how to create rules on your own.
You have to write your custom rules inside your init.php after the module integration. For demonstration purposes I will show you how to add a custom rule named allFail. This rule makes no sense and returns always false if you enter a value inside the input, where you have added it.

```php
$forms = new FrontendForms();
$forms->setLang('de');

$forms->Validator::addRule('allFail', function ($field, $value, array $params) {
  return false;
}, 'is wrong');
```
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


## SPAM protection
There are multiple traps for spambots included.

### Honeypot field
A honeypot field, which changes the position on every page load in random order, will be added automatically by default. If you do not want to include the honeypot field you need to add the useHoneypot(false) method to you form object (not recommended).
Info: A honeypot field is a field which is hidden to the user, but a SPAM bot can read it and if this field will be filled out it will be detected as spam.
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

### Time measurement
You can set a min and max time for filling out the form. You only have to add the setMinTime() and/or setMaxTime()
method(s) to your form object. If a user or a SPAM bot submits the form outside this time range, the form will not be
submitted.
SPAM bots tend to fill out forms very quickly or analyse the forms very long and submit them after a long while.
So with this time trap you can detect SPAM bots.
Can be disabled by setting the values to zero.
After every submission the time will be calculated new. This means that the min time set in the configuration refers to
the time which is needed to fill out all empty required fields. If some required fields contain a value after submission, the time will be
reduced for filling out the form, because there are less field left. So checking the min time takes care about how many
fields are filled out at the time of submission.
What you have to do is to estimate how long it will take to fill out all required fields as minimum time for filling out
the form.

### Set max number of invalid attempts
You can set a number of max attempts for submitting the form successfully by adding the setMaxAttempts()
method to your form object.
If the number of unsuccessful attempts is higher than the allowed number the form submission will be blocked.
It is only a soft block by using a session. The user will be prompted to close the browser to remove the session and
to re-open the page again. If the session is active, the form will not be displayed on the page and therefore cannot be
filled out. SPAM bots fill out input fields sometimes randomly and therefore make a lot of mistakes.
Can be disabled by setting the value to zero.

## Prevent double form submission
Only to mention: There is also a session active which prevents double form submission after successful validation.
It compares the session value with the value of a hidden field. If the values are different, it is an indication that
the form would be submitted twice. In this case the submission will be stopped before it takes place, and you will
be redirected to the form page itself. 
The double-submission check can be disabled if necessary.

## IP-banning
Add IP-addresses to a blacklist to prevent them accessing your forms. If the visitor's IP is on this list, an alert box will be displayed,
which informs the visitor, that his IP is on the blacklist. The form itself will not be displayed.

## CAPTCHA
This module offers various type of CAPTCHA, that can be used. BTW: CAPTCHA should be used only if the other traps failed.
Most users do not like CAPTCHA, but it is up to you whether to use them or not.
At the moment, following CAPTCHA types will be provided:

### Image CAPTCHA
The image CAPTCHA shows an image and the user has to answer which category fits to the image. The following categories
exist at the moment: tee, house, lake, flower, animal, mountain, ship, car.
You can manipulate the image by using various filters (can be set in the configuration).

### Random string CAPTCHA
A random string will be provided inside an image and the user has to write this string into the input field below.

### Even string CAPTCHA
This is almost the same as the random string CAPTCHA with the only difference, that the user has to enter ever second
character (even character) and not the whole string.

### Reverse string CAPTCHA
This is also almost the same as the random string CAPTCHA with the only difference, that the user has to enter the characters
from right to left (reverse order).

### Math CAPTCHA
The user has to solve a simple calculation.

In the backend, there are a lot of configuration settings to adapt the CAPTCHA to your needs or to adapt it to your project
design. The settings are self explaining, so I do not want to go into detail.
The configuration is global and cannot be changed on per form base. The only thing that you can do is to
disable the CAPTCHA on per form base. This is useful if you want to use a CAPTCHA, but on certain forms (fe. forms for logged-in
users), you want to disable it.

```php
  $form->disableCaptcha();
```
### Password blacklist
If you are dealing with user login/registration on your site, there is always a risk, that clients use unsafe passwords
and this could be a serious security issue for an account to be hacked.
For this reason, you have a blacklist of forbidden passwords in the module configuration. To make it much more simple 
for you, it uses passwords from the top 100 most common passwords list from GitHub. In addition, you can add your own 
passwords too.

The best of the blacklist: 
It cares about your password requirement settings and does not add all 100 passwords of the top list by default to the
blacklist.
This means, only passwords that fulfill your password requirements will be added to the blacklist - all others will not 
be added to keep the list as short as possible (better performance).

Example:
Your password requirements as set in the field configuration of the field "pass" are set to "letter" and "number", which 
means each password must consists of at least one letter and number.
Passwords that does not fulfill this minimum requirements will be filtered out by the "meetsPasswordConditions" validator
before, which will be added by default to every password field.
So there is no need to add these passwords to the blacklist, because they do not fulfill the requirements at all.

On the module configuration page you will find a very detailed description how the blacklist works.

Only to mention:
The top 100 password list will be checked once a month on GitHub, if the file has been modified. Once a month is enough
and GitHub allows only a certain amount of requests per day. Otherwise you will get a 403 error.
So checking it once a month does not bomb their server with too much requests.
If something has been changed on the list, it will be downloaded and added to the blacklist automatically.
So the list is always up-to-date.

### Statistic section of blocked users to identify spammer
In addition to the IP-banning blacklist, a statistic section which informs you about users that have been blocked, is part
of the anti-spam measures.
A user will be blocked if, for example, he needs too many attempts to send the form (depending on your settings in the backend).
In this section you can get more information about this user, and you have 2 buttons to add or remove it to the IP blacklist.

## Module configuration settings
At the backend there are a lot of options for global settings. Fe you can choose if you want to add a wrapper container to the input field or not or if you want to add an outer wrapper to the complete form field (including label, input field, description, notes,...).
Nearly each CSS class for the various form elements can be overwritten too. So you can use your own preferred class names if you want - no Hooks are necessary.
Take a look at the configuration page - all different settings are described there.

## Support for UiKit 3 and Bootstrap 5
In the backend you can select if you want to render the markup with UiKit 3, Bootstrap 5 or no framework.

## General methods
General methods are methods that can be used on each object: form, input field, label, description, notes, wrappers, fieldset,...

### setAttribute()
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

### setAttributes()
You can also set multiple attributes at once, but you have to put the attributes inside an array.

```php
  $field->setAttributes(['id' => 'myId', 'class' => 'myClass']);
```

### removeAttribute()
You can remove an attribute by adding the attribute name inside the parenthesis. In this case you will remove the attribute completely.

```php
  $field->removeAttribute('class'); // this removes the class attribute completely from the tag
```

### removeAttributeValue()
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

### prepend(), append()
You can prepend/append a string before and after an object. So you can add additional markup if you want.

```php
  $field->prepend('<div class="grid">')->append('</div>');  
```

## Form and its methods

The form object holds all the input fields, fieldsets, additional markup,...

```php
  // instantiating a new form object
  $form = new Form('myForm');
```

### Methods for the form

#### useFieldWrapper(), getFieldWrapper()
Add, remove or get the most outer container for form fields.
The useFieldWrapper() method overwrites the settings in the module configuration.
The getFieldWrapper() method returns the Field wrapper object, so you can manipulate it, if you need.

```php
$form = new Form('myForm');
$form->useFieldWrapper(true); // add the field wrapper to all input elements
$form->useFieldWrapper(false); // remove the field wrapper from all input elements
$form->getFieldWrapper()->setAttribute('class', 'newClass')->removeAttribute('class', 'oldClass'); // customize the wrapper object
```
#### useFieldWrapper(), useInputWrapper()
Add or remove the fieldwrapper and inputwrapper from the form elements.
These methods overwrite the settings in the module configuration.

```php
$form = new Form('myForm');
// field wrapper
$form->useFieldWrapper(true); // add the field wrapper to all input elements - this is the defaults setting
$form->useFieldWrapper(false); // remove the field wrapper from all input elements
// same for input wrapper 
$form->useInputWrapper(true); // add the input wrapper to all input elements - this is the defaults setting
$form->useInputWrapper(false); // remove the input wrapper from all input elements
```

#### getFieldWrapper(), getInputWrapper()
With these methods you can grab the wrapper object for further manipulations, if needed


```php
$form = new Form('myForm');
// input wrapper object
$form->getInputWrapper()->setAttribute('class', 'newClass')->removeAttribute('class', 'oldClass'); // customize the input wrapper object
// and the same for the field wrapper object
$form->getFieldWrapper()->setAttribute('class', 'newClass')->removeAttribute('class', 'oldClass'); // customize the field wrapper object
```
#### useHoneypot()
This will add or remove the honeypot field. Enter true or false as parameter

```php
  $form->useHoneypot(false); // this removes the honeypot field form the form
    $form->useHoneypot(true); // this will add the honeypot field to the form - this is the default setting
```

#### useDoubleFormSubmissionCheck()
This will enable/disable the checking of double form submission. This is useful on profile forms, where you can change 
your data multiple times.

```php
  $form->useDoubleFormSubmissionCheck(true); // double form submission check will be enabled - this is the default setting
  $form->useDoubleFormSubmissionCheck(false); // double form submission check will be disabled on the form
```

#### setRequiredText()
With this method you can overwrite the default hint that will be displayed on the form to inform the user that he has to fill all required fields marked with an asterisk.

```php
  $form->setRequiredText('Please fill out all required fields');
```

#### setRequiredTextPosition()
With this method you can overwrite the position of the required text in the global settings in the backend. As parameter, you have none, top or bottom. If set to top, the text will be displayed above the form, otherwise below. If you choose none, then the text will not be displayed at all.

```php
  $form->setRequiredTextPosition('bottom');
```

#### setMethod()
Set the form method (post, get). If you want to use post as your method, you do not need to add this method explicitly, because this method was set as the default method.

```php
  $form->setMethod('post');
```
#### setMinTime(), setMaxTime()

Set the min and max time for form submission in seconds. The form will only be submitted if the submission time is in between the time range.

```php
  $form->setMinTime(5);
  $form->setMaxTime(3600);
```
#### setMaxAttempts()

Set the max number of attempts to submit a form successful. If the number of unsuccessful attempts is higher than the max number of attempts, the form submission will be blocked.

```php
  $form->setMaxAttempts(10);
```

#### setLang()

Set the language of each form individually by using the setLang() method.

```php
  $form->setLang('de');
```
Please note: It is recommended to set the language globally inside the init.php. In this case you do not have
to set the language on each form.

#### getValues()
This method returns all form values after successful validation as an array. Use this method to process the values further (fe send via email).
By default, this method only returns values from inputfields. If you need values from buttons to, please add true inside the parenthesis.

```php
  $form->getValues();
```

```php
  $form->getValues(true); // this also outputs the value of a button (fe send) if needed
```
#### getValuesAsString()
This method is the same as the getValues() method, but it returns all post values as a string instead of an array.

```php
  $form->getValues();
```
#### getValue()
This will return the value of a specific input field after a successful form submission. You have to write the name of the input field inside the parenthesis.
```php
  $form->getValue('subject'); // this will return the value of the input field with the name attribute subject
```
#### add()
This is the method to add a field to the form. You have to enter the field object inside the parenthesis.
```php
  $form->add($field);
```

#### remove()
This is the method to remove a field from the form. You have to enter the field object inside the parenthesis.
```php
  $form->remove($field);
```
#### getFormelementByName()
Grab a form element by its name attribute - returns the field object for further manipulation.
Fe if you want to get the field with the name attribute "email" add "email" as parameter inside the parenthesis, and you will get the form field object as return value.
```php
  $form->getFormelementByName($fieldname); // fieldname could be fe email, pass or whatever
```

#### setErrorMsg()
With this method you can overwrite the default error message which appears inside the alert box after an unsuccessful form submission.
```php
  $form->setErrorMsg('Sorry, but there are errors!');
```
#### setSuccessMsg()
With this method you can overwrite the default success message which appears inside the alert box after a successful form submission.
```php
  $form->setSuccessMsg('Congratulations, your message was submitted successfully!');
```
#### useFormElementsWrapper()
With this method you can wrap all form fields in an extra div, or remove the wrapper
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

#### getFormElementsWrapper()
This method returns the wrapper object for all form fields for fe further manipulations.
```php
  $form->getFormElementsWrapper();
```

#### appendLabelOnCheckboxes() and appendLabelOnRadios() for checkboxes and radio buttons
By default, all checkboxes and radio buttons are wrapped by their label tag.

```html
<label><input type="checkbox">Checkbox Label</label>
```
Sometimes it is necessary (fe. by using certain CSS frameworks), that the label tag is after the input tag.
```html
<input type="checkbox"><label>Checkbox Label</label>
```
For this case you can use these two methods, which should be added to the form object.
You can set this behavior globally in the module configuration, but you can overwrite it on per form base if needed.

```php
$form->appendLabelOnCheckboxes(); // or
$form->appendLabelOnCheckboxes(true); //appends the label after the input tag
```

```php
$form->appendLabelOnCheckboxes(false); // the input tag will be wrapped by the label tag
```
You can do the same for radio buttons by using the appendLabelOnRadios() method.

#### setUploadPath()
If you are using a file upload field on your form, by default all uploaded files will be stored inside the "temp_uploads"
folder of this module. This is the default folder, for storing the files and if you are sending emails with attachment,
the module grabs the files for attachments from this "temp_uploads" folder and remove all files inside this folder after
successful submission.
So this folder will not be the right place if you want to store files permanently. For this case you have the possibility
to change the upload folder. 
If you are adding true as second parameter inside the parenthesis, the folder will be created if it does not exist, otherwise you will get 
an error message, that this folder does not exist.
All files will be saved at site/assets/files, which is the Processwire files directory

```php
$form->setUploadPath('mycustomfolder/', true); // the files will be stored at site/assets/files/mycustomfolder/
```
This works before and after POST. If you use this method before POST, then all files will be stored directly inside the 
given folder.
If the method is used after POST, then the files will be stored inside the temp_uploads folder first and will be moved to the
new directory afterwards.

#### IP banning
You have the possibility to add IP addresses to a blacklist in the module configuration.
This means that visitors with an IP listed in this list are not able to view a form
on the page. This setting is global for all forms created with this module, but you can
change the settings on per form base if needed. Take a look at the following methods.
Only to mention: The visitor can visit the page, but the form is not visible for him (as written some lines below).

##### useIPBan()
You can disable the checking of the IP address on each form manually.

```php
$form->useIPBan(true); // IP checking will be enabled on this form - this is the default value
$form->useIPBan(false); // IP checking will be disabled on this form
```
##### testIPBan()
This is only a testing method to check if IP banning works.
Enter an IP address inside the module configuration and then use this method on the form by entering the IP inside the parenthesis.

```php
$form->testIPBan('146.70.36.200'); // 
```
By visiting the page, an alert box will be displayed on the frontend instead of the form. Try it out to see how it works.

#### logFailedAttempts()
You can enable/disable the logging of blocked visitors IP on per form base.

```php
$form->logFailedAttempts(true); // blocked visitors will be logged on this form
$form->logFailedAttempts(false); // no log entry will be written if visitors are blocked -> this is the default setting
```
#### isValid()
This is the most important method. It takes the user data, sanitizes it, validates it and outputs possible errors or the success message.
Returns true or false after form submission. You have to use this method to process the submitted form data further.
```php
  $form->isValid();
```

#### isBlocked()

If you want to do another logic if a user was blocked, then use the isBlocked() method and run your code inside it.
Only to mention: A user will be blocked if the max number of attempts to submit the form with success was reached.

```php
  $form = new Form('myForm');
  $form->setMaxAttempts(10);
  ....
  if($form->isBlocked()){
    .....
  }
  $form->render()
```
#### render()
Render the form on the page.
```php
  $form->render();
```
## Input fields and their methods

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
The field wrapper is the most outer container. You can enable/disable it in the global settings in the backend. But you can overwrite the global settings on each form individually by using the useFieldwrapper() method at the form object.
If you want to customize the field wrapper you can use the getFieldWrapper() method which returns the field wrapper object itself.

#### Input wrapper
This is a container element around the input field. You can set or remove it in the same way as the field wrapper by using the useInputWrapper() and getInputWrapper() methods.

#### Label, notes, description
These elements do not need a more detailed explanation. Only to mention here: you can customize all of them by chaining methods to set/remove attributes.

### General Methods for input fields
These methods can be used on each input field.

#### setLabel()

Method to add a label to the form field. Returns a label object.

```php
$field->setLabel('E-Mail address');
```

#### setNotes()

Method to add notes to the form field. Returns a notes object.

```php
$field->setNotes('You have to fill out this field');
```

#### setDescription()

Method to add a description to the form field. Returns a description object.

```php
$field->setDescription('This text describes the input field more in detail');
```

#### setSanitizer()

Method to add a sanitizer to the form field. Returns a sanitizer object. You can use all ProcessWire sanitizer methods by adding the sanitizer name inside the parenthesis of the setSanitizer() method. You can also set multiple sanitizer methods to one field if necessary.
Please note: For security reasons, the text sanitizer will be applied to each input field automatically, so you do not have to add it manually. The only exception is input textarea, where a textarea sanitizer will be applied by default.

```php
$field->setSanitizer('text');
```

#### hasSanitizer()
If you want to check if an input field has the given sanitizer, you can use this method.
Returns true or false.

```php
$field->hasSanitizer('text');
```

#### removeSanitizers()
You can remove all sanitizers (including the sanitizers applied by default) with this method if you enter no value inside the parenthesis.
By entering one sanitizer as a string you can remove only this one.
By entering multiple sanitizers as an array, you can remove multiple sanitizers at once.

```php
$field->removeSanitizers(); // removes all sanitizers from this input field
$field->removeSanitizers('text'); // removes only the text sanitizer from this input field
$field->removeSanitizers(['text', 'number']); // removes multiple sanitizers from this input field
```

#### setRule()

Method to add a validator to the form field. You can find examples of all validators in the validationTypes.php inside the 'examples' folder. Add the name of the validator  inside the parenthesis of the setRule() method. You can also set multiple validation methods to one field if necessary.

```php
$field->setRule('required');
$field->setRule('number');
```

#### removeRule()
This is the opposite of setRule(). You can remove unwanted rules with this method. This is useful if you use pre-defined Inputs, which contains some validation rules by default.

```php
$field->removeRule('required');
```

#### hasRule()
If you want to know if a certain field has a specific rule added, you can use this method. Add the name of the rule as
the parameter inside the parenthesis and the method returns boolean true or false.

```php
$field->hasRule('required');
```

#### getErrorMessage()
You can use this method to manipulate attributes of the error message on per field base.
```php
$field->getErrorMessage()->setAttribute('class', 'myErrorClass');
```

#### setDefaultValue()
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

#### setChecked() for single checkboxes without values
As written above, setDefaultValue() can only be used for inputs with values.
Single checkboxes do not need values. To make them checked by default, you have to use
the setChecked() method instead of the setDefaultValue() method.
Using this method is necessary and the only way to mark checkboxes without a value attribute by default.
Please note: This method works on checkboxes with value too, but in this case it is
recommended for consistency to use the getDefaultValue() method.

```php
// checkbox with no value
$checkbox = new Checkbox('singlecheckbox')
$checkbox->setChecked();
```

#### render()
Method to render an input field. You do not need this method until you want to render an input field on its own.
```php
$field->render();
```

### Special Methods for input fields
These methods can only be used on certain input fields.

#### alignVertical() for checkboxes and radio buttons
This is only a method for multiple checkboxes and radio buttons to align them vertical instead of horizontal.

```php
$checkbox = new InputCheckboxMultiple('myCheckbox')
$checkbox->alignVertical();
```

#### addOption() for checkboxes, radio buttons, select and datalist
Method for multiple checkboxes, multiple radio buttons, select and datalist inputs to add an option element. As parameters, you have to add the label as first and the value as second parameter. Afterwards is an example with a multiple checkbox.

```php
$checkbox = new InputCheckboxMultiple('myCheckbox');
$checkbox->addOption('Checkbox 1', '1');
$checkbox->addOption('Checkbox 2', '2');
```

#### addEmptyOption() for select and select multiple

```php
$select = new SelectMultiple('mySelect');
$select->addEmptyOption('Please select your choice');
$select->addOption('Value 1', '1');
$select->addOption('Value 2', '2');
```
By adding the addEmptyOption() method, the first option in line will not be selected by default. You do not need to add a text inside the parenthesis. By default "-" will be shown.
Just add it to your select to see how it works.

#### setOptionsFromField() for select multiple, radio multiple, checkbox multiple and datalist input fields

```php
$select = new SelectMultiple('myField');
$select->setOptionsFromField('mypwfieldname');
```

With this method you can use the options from a ProcessWire field of the type FieldtypeOptions for a datalist, checkbox multiple, radio multiple or select field.
You only have to enter the name of the PW field and the field must be of the type FieldtypeOptions.
Otherwise, it will not work.

#### showPasswordRequirements() for password fields
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

#### showPasswordToggle() for password fields
This method can be added to a password field and adds a checkbox below the input field. If the checkbox will be checked, the password entered will be displayed in plain text, otherwise not.
JavaScript is used to show/hide the password in plain text.

```php
$password = new InputPassword('password');
$password->showPasswordToggle();
```

#### sendAttachment() for file input fields
This method has to be used with the WireMail class. It is the same as the WireMail attachment() method, but it has some
extra functionality. It saves the uploaded files in a pre-defined temp folder called "temp_uploads".
You do not need to enter the path to the files manually. After the files were sent, all files in the temp folder will be
deleted.
You can disable the deletion of the files afterwards if you enter true inside the parenthesis.
Just to mention, you have the possibility at the form object to change the path to your upload folder with the
setUploadPath() method to your own preference. Do not do this on forms that send emails with attachments. It will only
work if the attachment files will be uploaded in the "temp_uploads" folder.

```php
$m = wireMail();
$m->sendAttachment(); // attachments will be deleted after sending

$m = wireMail();
$m->sendAttachment(true); // attachments will not be deleted after sending
```
Take a look at the contact form in the example folder which uses file upload too.

#### allowMultiple() for file input fields
By default, file upload fields only allow to upload 1 file. With this method you can change this behaviour by adding true
or false inside the parenthesis:
True: renders a multiple upload field
False: renders a single upload field 

```php
$m = wireMail();
$m->allowMultiple(true);
```
#### mailTemplate() to change the email template
With this method you can overwrite the global setting from the module configuration. Use this if you want to use another
template for sending emails on that form. 
If setting it to 'none', no template will be used.

```php
$m = wireMail();
$m->mailTemplate('template1');
```

## Customization of validation
For each validator, there is a pre-defined error message inside the lang folder. This is ok for most cases, but 
sometimes you need to show another error message than the pre-defined one. For these cases you can customize your error 
messages with 2 methods.

Default error message:
By default the error message uses the name of the input field and prepends it before the error message.

```php
$field = new InputCheckbox('privacy')
$field->seRule('required');
```
If the validation fails, the error message will look like this:
Privacy is required

If you do not want that the name of the field (in this case privacy) should be used, then you can change this in the 
following way:

### setCustomFieldName()
By using the setCustomFieldName() method you can change the name of the input field in the error message

```php
$field = new InputCheckbox('privacy')
$field->seRule('required')->setCustomFieldName('This field');
```
If the validation fails, the error message will look like this:
"This field is required" instead of "privacy is required".

### setCustomMessage()
Use this method if you want to overwrite the default error message completely.

```php
$field = new InputCheckbox('privacy')
$field->seRule('required')->setCustomMessage('You must accept our privacy policy');
```
If the validation fails, the error message will look like this:
You must accept our privacy policy

## Fieldsets
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

## Buttons
Here is an example of a form button.

```php
$buttonReset = new Button('reset');
$buttonReset->setAttribute('type','reset');
$buttonReset->setAttribute('value', 'Reset');
$buttonReset->addWrapper()->setAttribute('class', 'myButtonWrapper');
$form->add($buttonReset);
```

## Default fields
To make life a little easier I have created the most common fields in forms as pre-defined default fields with 
its own class.

These are: 

* Email (Input text to enter an email address - class name: Email)
* Name (Input text to enter a name - class name: Name)
* Surname (Input text to enter a surname - class name: Surname)
* Password (Input password to enter a password - class name: Password)
* Password confirmation (Input password to enter the password confirmation - class name: PasswordConfirmation)
* Privacy (Checkbox to accept the data privacy - class name: Privacy)
* SendCopy (Checkbox to force the sending a copy of the text of a contact form to me - class name: SendCopy)
* Subject (Input text to enter a subject - class name: Subject)
* Message (Textarea to enter a text - class name: Message)
* Gender (Select to choose the gender - class name: Gender)
* Username (Input text to enter a username - class name: Username)

Instead of creating this type of input fields every time on your own, you can use the pre-defined input types as listed above.
Every input type has the validation rules and sanitizers included, the labels and error messages are defined and instead of writing 

```php
    $emailfield = new \FrontendForms\InputfieldText('myemailfield');
    $emailfield->setLabel($this->_('Email'));
    $emailfield->setRule('required')->setCustomFieldName($this->_('Email'));
    $emailfield->setRule('email');
    $emailfield->setRule('emailDNS');
```

you only need to write 
```php
    $emailfield = new \FrontendForms\Email();
```

This is the same as the example above but shorter and nicer and you can be sure, that every email field is the same.

You can do the same with all others mentioned pre-defined input types. You only have to instantiate the class of the input type and add the field to the form.
BTW you will find all pre-defined input types inside the "defaults" folders.

- Formelements/Inputelements/Inputs/defaults/
- Formelements/Inputelements/Select/defaults/
- Formelements/Inputelements/Textarea/defaults/

## File uploads
There are 2 scenarios of uploading files with FrontendForms:

1. Upload a file for sending it with an email
2. Upload a file for storing it under site/assets/files

If you want to see a working real world example, please take a look at the example page at site/modules/FrontendForms/Examples/fileuploadtopage.php inside the Examples folder.

### Upload a file for sending it with an email
In this case you have to add the sendAttachement() method to the WireMail object. Otherwise the files will not be sent with the email. You will find more information about the sendAttachements() method here.

### Upload a file for storing it under site/assets/files
The site/assets/files ist the Processwire directory where all the file will be stored. This directory is public reachable, so that the files could be fetched via fe a link.
If you want to upload a file under this directory you have to use the setUploadPath() method of the form. With this method you set the target folder, where the file should be stored after the upload.
You will find more information about the setUploadPath() method here.

## Hooking
Hooking is not really necessary in most cases, because you have so much configuration options to achieve your desired 
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
$wire->addHookAfter('Errormessage::render', function(HookEvent $event) {
  $alert = $event->object;
  $fontAwesome = '<i class="fas fa-exclamation-triangle"></i>';
  $alertText = $alert->getText();
  $alert->setText($fontAwesome.$alertText);
  $event->return = $alert->___render();
});
```
After:
```html
<p class="uk-text-error"><i class="fas fa-exclamation-triangle"></i>This is the error message.</p>
```
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
Unfortunately this class does not support the usage of stylish HTML email templates by default, so I have decided to enhance 
this class with a new method to simply choose an email template, which is stored inside the email_templates folder.

### New method mailTemplate()
First you need to know, that inside the email_templates folder you will find HTML files with various names 
(fe template_1.html, template_2.html,...).
These files are the one you can use as the parameter within the brackets of this method:
You can find a select list of all existing email templates files inside the module configuration page too.

```php
$mail = new WireMail();
$mail->mailTemplate('template_1.html'); // this adds the template with the file template_1.html to the email
```
These files are email templates, and they contain placeholder for your content.
F.e. the text of the subject will be rendered inside the placeholder [[SUBJECTVALUE]],
the text of the body inside the placeholder [[SUBJECTVALUE]].
I have decided to use the double square brackets syntax, because this syntax is also used in the Hanna code module.
It is recommended to take a look at the email templates which are shipped with this module. You can take them as an 
example on how to write your own email templates (or download free templates from the internet and add the placeholders 
by yourself).

If you have created a template on your own, add it to the email_templates folder as the other templates, and now you are
ready to use it.

Using this method renders your email as a stylish HTML email using the selected email template.

### Images inside email templates
You can also use images in your email templates, but be aware to use the absolute URL to this image (not relative).
Template file "template_2.html" uses the ProcessWire logo with an absolute URL from the internet. You can take a look
at this template file how to include images in email templates.

#### Storage place for images for email templates
This module creates a new folder inside the site/assets/files folder of PW called FrontendForms during the module
installation. This folder is for images that you can use within the email templates. This folder has public readability
and can be reached from outside. This is necessary to view the images inside the emails.
Before the module will be installed, there is also a folder called assets inside this module. It contains images for the
ready to use email templates.
During the installation process, these images will be copied from the assets folder inside the module to the newly 
created FrontedForms directory. The assets folder inside the module directory will be deleted afterwards.
If you uninstall the module, the assets folder will be copied back to the module folder. Afterwards the FrontendForms 
folder will be removed from the site/assets directory.
So if you are creating your own email template, please put the images inside the FrontendForms
folder. It is recommended to use a separate folder for each template (fe. site/assets/FrontendForms/mytemplateFolder).

You can find an example of using a local stored image inside the "template_3.html" file. So take a look there how to 
accomplish this.
Please note: If you are running your site on a local server (XAMPP, WAMPP,..), local stored images will not be displayed
inside the emails because they are locally and therefore not reachable via the internet. 
After you have transferred your site to a live server, the images should be displayed properly inside the emails.

### Placeholder variables for usage in email templates and email body
Placeholder variables are variables that can be integrated easily in the email templates. Their purpose is to add some 
data to the email templates without using php code. They can also be used for the body text of the email (you will find
a real world example in the contactform.php inside the examples folder).
Each placeholder variable is surrounded by 2 square brackets (fe. [[DATEVALUE]]) and has to be written in 
uppercase letters.
This variable will be replaced on the fly by the appropriate value, provided by a php code.
For example: If you want to add the current date to a template, you only have to write [[CURRENTDATEVALUE]] and this placeholder
will be replaced by the current date.

There are a lot more placeholder variables: You can take a look if you are using the method getPlaceholders() on your form 
object. You will get an array of all available placeholders, that can be used.

```php
$form = getPlaceholders();
```

A placeholder for each will field of a form will be created automatically.
Assume you have an input field in your form for the name and the name/id attribute of this field is "myname".
2 Placeholder will be created automatically for the label and the value of the field and both can
be used inside the email template or the email body text.

Take a look at the form:

```php
$namefield = new \FrontendForms\InputfieldText('myname');
$namefield->setLabel($this->_('My name'));
```

As you can see, the inputfield has the name/id "myname."
The placeholders, that will be created automatically are:

[[MYNAMELABEL]] and [[MYNAMEVALUE]] 

So it takes the name/id attribute of the form field and adds LABEL for the label value and VALUE for the value itself.
You can use both of them inside the body variable of your form. Both placeholders will be replaced by their appropriate
values before the email has been sent.

So beside the global placeholders you can use also placeholder values of fields inside the form.

Only to mention: The primary reason for placeholders is the usage inside templates, and for later usage inside modules,
which descend from this module, but you can also use it inside your mail body variable, which contains the content of the
email.

```php
$mail->body(_('These are the form values: [[SURNAMELABEL]]: [[SURNAMEVALUE]], [[NAMELABEL]]: [[NAMEVALUE]]'));
```

So there will be no need to include the values with php code, but you can do it, if you want. 

#### Adding custom placeholders for usage in templates
If you think about creating or using a custom HTML email template, and you want to use a special placeholder there
(fe the name of your company), you can add a custom placeholder to your form with the following method.

```php
$form->setPlaceholder('companyname', 'My company');
```
Then you will be able to add the custom placeholder to your new email template: [[COMPANYNAME]].
Before sending of the email the placeholder will be replaced by "My company".

BTW, adding the company name via a placeholder is not really a good example, because the company name is always
the same, and therefore it can be added hardcoded to the template (no need for creating a placeholder).
A better example would be fe an order number, because this number is variable.

```php
$form->setPlaceholder('ordernumber', '123456');
```
By using a placeholder variable, there is no need to add php code to the email template to grab the order number.
This makes the email template code much more cleaner.

### Following Placeholders are supported by default for the usage in HTML email template

* {TITLE} : Renders the value of the $mail->title inside the template.
* {SUBJECT} : Renders the value of the $mail->subject inside the template.
* {BODY} : Renders the value of the $mail->body inside the template.
* {USERNAME} : Renders the value of the username inside the template, if the user is logged in.
* {DOMAIN}: Renders the domain of the site (fe http://www.mysite.com)

#### New method title()

The title method adds a title attribute to the HTML template which will be displayed under the subject. You can also output it inside your template with the placeholder {TITLE}

```php
$mail = new WireMail();
$mail->title('This is my title');
```

#### Add and output a custom placeholder inside your email template with addPlaceholder() method

The addPlaceholder() method consists of 2 parameters: placeholder name and placeholder value.

```php
$mail = new WireMail();
$mail->addPlaceholder('date', '01.01.2022');
```
The placeholder is always the name in uppercase letters and inside brackets: {DATE}
With the example above you can use the placeholder {DATE} inside your mail template to output '01.01.2022' inside the template (in this case).
