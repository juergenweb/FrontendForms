# Change Log
All notable changes to this project will be documented in this file.

## [1.0.1] - 2021-08-16

### Added
- New validation rule 'uniqueEmail' was added.
  Checks if an email address is used by another user or not - useful for registration and profile form.


## [1.0.2] - 2021-08-19

### Added
- New validation rule 'checkPasswordOfUser' was added.
  This validation rule is for logged-in users only. Idea: If you want to change your password you have to enter the old 
  password before. And for that reason I have created this rule. So this rule is for a password field where you have to
  enter the current password for security reasons - useful for the profile form.

## [1.0.3] - 2021-08-25

### Added
- New validation rule 'matchEmail' was added.
  This validation rule is if you want to use email and password for login instead of username and password. So it checks
  if password and email match. This rule has to be applied to the password field on login forms.

## [2.0.0] - 2021-11-05

### Added
- New methods for WireMail class
  2 new methods for the WireMail class were added to use HTML email template

### Corrections
- various corrections of bugs and translations

## [2.0.2] - 2021-12-20

## [2.0.2] - 2022-05-07

### 3 new methods added to the Button class
- addWrapper(): adds a wrapper around the button element
- removeWrapper(): remove an existing wrapper from the button element
- getWrapper(): returns instance of the button element (not really necessary ;-))

## [2.0.4] - 2022-05-30

### Add form id as prefix to name attribute of form elements
To prevent collisions of name attributes on multiple forms on one page, all name attributes will be extended with the 
form id as prefix.
Fe the form id is "contact-form" and the name attribute of the email input field is set to "email", then the name
attribute of this field will be "contact-form-email". 
This is useful if you have fe 2 different contact forms on one page and each form has a field with the name "email". If
you submit the first form, and you have entered a value in the email field, than the value will be automatically entered
the second form too. So the email field value will be present in both forms after post. This addition prevents this
behavior.

## [2.0.5] - 2022-06-06

### Add new method addEmptyOption() added to select tags
With this new method you can add an empty option value at the top of your select.
Fe an option with the text "Please select your choice".
Code of all other files was refactored and optimized with PHP-Storm. Of course, it needs additional testing too. 

## [2.0.6] - 2022-06-17

### Move all validation methods to an extra class
A new class called FormValidation was created to hold all validation methods. So the code is better readable.
The problem of getting POST values of multiple value fields (fe select multiple, checkbox multiple) was fixed.

## [2.0.7] - 2022-06-17

### New method to add options from a PW field to Select and Datalist fields 
With the method setOptionsFromField(string $fieldName) you can set the options of a field in ProcessWire to a select
multiple, radio multiple, checkbox multiple or a datalist field.
You only have to enter the name of the ProcessWire field and the field must be of the type "FieldtypeOptions".
This method adds all options of the PW field as options to the input field.

## [2.0.8] - 2022-06-23

### New configuration to set the page which contains the privacy policy added
A new select on the configuration page in the backend let you select, which page contains the data privacy.
This is the global selection and can be overwritten on the Privacy class with the new methods afterwards.

### New methods to Privacy class added
The setPrivacyUrl() and getPrivacyUrl() let you set/get the url of the privacy policy page manually.
Usually you do not need them if you set the privacy policy page global inside the module configuration as written above.

### New methods to Link class added
The setAnchor() and getAnchor() methods allow you set/get internal anchors of a link.
The setAnchor() method adds the anchor after the url.

## [2.0.9] - 2022-06-27

### New methods useFormElementsWrapper() and getFormElementsWrapper() added
With these new methods (inspired by a request from Anonimas in the PW forum) you can add a div wrapper between the form
tags in an easy way.
A unique CSS id will be added automatically, but you are able to add other attributes too.

## [2.1.0] - 2022-06-27

### New method useDoubleFormSubmissionCheck() and setRequiredText() added
Adding the first method to the form object with parameter true or false disables or enables the check for double form submissions.
This is useful on profile forms, where you can change your data multiple times.
With the second method you can overwrite the default text to inform the user, that every required field, marked with an
asterisk, has to be filled out.

## [2.1.1] - 2022-07-07

### Upgrading to PHP 8.0, adding new methods and backend configuration to append the label on checkboxes
and radios after the input tag on demand, fixing a lot of errors
Allowing to set values of setDefaultValue() method also as an array and not only as a string.
Adding support for boolean parameter in getValues() method, whether to output only input field values or values from
buttons too. 
New method setLang() added. Now you can set the language on per form base by adding this method to the form object.
New Method getValuesAsString() added. This method is similar to the getValues() method, but it outputs all post values
as a string.

## [2.1.2] - 2022-07-08
### Adding new backend configurations
#### Add or remove extra wrapper div 
If you need an extra markup div over all form fields you can use this setting to add 
an extra div between the opening and closing form tag. 
You can also enter a global CSS class for this wrapper div.
#### Add or remove the CSS file for honeypot field on frontend
By default, a small CSS-file to hide the honeypot-field will be added. You can disable the embedding of this file in the
backend.
#### Set max attempts for form submission
Set max number of failed attempts globally until a visitor will be blocked.
#### Set min before a form should be submitted
Set min time before the submit button should be pressed to prevent spam-bots filling out the form.
#### Set max until a form should be submitted
Set max time until the submit button should be pressed to prevent spam-bots analyzing the form.
#### Enable/disable logging of failed attempts
Possibility to save every blocked IP address including name of the form and date/time as timestamp to the log files.
#### Create list of black listed IP addresses
Enter IP addresses that should be prevented from visiting the forms (fe IP addresses from spammers,...)
#### Set global custom texts for required fields, success and error message 
Now you can enter your custom messages inside the backend configuration.

## [2.1.3] - 2022-07-13
### Adding file upload for sending emails with attachments
New class InputFile added. It allows to upload files (single or multiple) that can be sent via email.
The depreciated method addEmailTemplate was replaced be the new method mailTemplate(). This method can
overwrite the chosen template from the global configuration if needed
To send emails with attachments you need to use the new method sendAttachments() instead of the WireMail
method attachment().
The checkTimeDiff() method was developed further. Now the min time measurement considers the progress of filling out the
form. Every required field that was filled out reduces the min time set. So fe the min time for filling out the form
was set to 5 seconds, and you have 5 required fields in your form. This means every field should take at least 1 second
to be filled out. If there are fe 3 empty required fields left after submission the min time will be reduced to 3 seconds
and so on. The time will be automatically calculated in the background.
New configuration field in the backend for enabling/disabling the checking of banned IP addresses added.
A new method will be added to the form class: setUploadPath(). With this method you can change the path to a custom
director, where uploaded files should be stored.

## [2.1.4] - 2022-07-19
### Adding statistic section to show data from the log files 
A new statistic section will be added, that shows all IP addresses and the number of their blocking events. You can add
or remove them to the blacklist bei clicking a button. 
A detail button provides more detailed information about the IP address and the blocking events.
Logging must be enabled in this case.

## [2.1.4] - 2022-07-25
### Re-writing getValues() and setUploadPath() methods
The getValues() methods outputs now the names of upload files. If the file was uploaded via a single upload field,
the value is a string, if it was uploaded via a multiple upload field the value is an array.
The setUploadPath() method supports now the changing of the upload path after POST too.

## [2.1.7] - 2022-10-09
### ID inside the constructor of the form is no longer necessary
The new method randomID() offers the possibility to avoid adding an own ID inside the constructor of the form.
This method was implemented during the development of the FrontendContact module. 
This method checks if an ID was set inside the constructor. If not, a random ID will be generated. This should prevent
problems that occur if the ID will not be set manually like during the instantiation of the contact form.
As a side effect, it is also comfortable for the user, if he does not need to add an ID manually.

## [2.1.8] - 2022-11-10
### Adding user property for further usage in all child classes
The property user ($this->user) was added to the constructor of the Form.php. This property hold the user object who is visiting the form. This property can be used in all child classes, whenever necessary. Version was bumbed to 2.1.8, because future classes, which uses this property have to use this version to work.

## [2.1.9] - 2022-12-01
### New validator added
New validator "safePassword" was added to check passwords against a blacklist of forbidden passwords. This list can be 
created in the backend. This validator was especially designed for password fields, where the chooser has to choose a new
password. This validation should prevent, that the user chooses an unsafe password and therefore makes his account more 
likely to be hacked.
As an important addition, a list containing the 100 most common passwords can be used with your blacklist.
### New placeholders added
A lot of new placeholder, that can be used inside mail templates where added. You can crab the list by running the
getMailPlaceholders() on the Form object.
Some of the new placeholders include current time, current date, ip address and so on. 
### Placeholders can also be used inside the body of the mail
In previous versions of this module, the email body could only consist of text. Now it is possible to add placeholders
into the email body and they will be replaced on the fly by the appropriate value.
### Corrections of code and translation
Last but not least, a lot of corrections/improvements of the code and translation strings were made.

## [2.1.12] - 2023-02-22
This versions comes with major improvements and changes. If you are upgrading please note, that there are some changes
according uploading files and sending of attachments. 
To make it easier for you all examples at the Examples folder were update, so you can see how do it now.

In addition HTML5 browser validation was added as a configurable setting. This means you can turn on browser validation 
by checking a checkbox in the admin if you want.

Some pre-defined input types for added for easier usage (FileUploadSingle, FileUploadMultiple).

A lot of bugs were corrected 

## [2.1.13] - 2023-02-23

New language configuration field for setting the language for Valitron error messages added to the module configuration. Now it is no longer needed to add language settings to the _init.php  file inside the templates folder.

## [2.1.14] - 2023-02-24
New language configuration fields have been removed again and the default Valitron error message language files inside the lang folder have been removed and have been replaced by translatable ProcessWire language strings by adding a new file (errormessages.php) to the valitron directory. 
Now nothing has to be taken into account get the correct user language for the messages on the frontend.

## [2.1.15] - 2023-02-26
Correct problems by validation of files and error messages of custom validators only beeing displayed in the default language (independent of language settings on the frontend)

## [2.1.16] - 2023-03-06
Added some minor fixes and a new default inputfield: Language.
Language is a new default pre-defined input field, which renders a select input field containing all languages installed on the site. This can be used to offer users to select a language.

## [2.1.17] - 2023-03-08
Some minor bugs inside the Tag class fixed.

A lot of new HTML input pattern for browser validation added, including custom error messages on failure.

Language file de.csv updated with new translations.

New example file for field validation added to the examples folder (field-validation.php). It is a test page and for
developers to look how to write specific field validation rules.

## [2.1.18] - 2023-03-10
2 new validation rules added:

1) week: validates a week in the format YYYY-Www (fe 2023-W12)
2) month: validates a month in the format YYYY-MM (fe 2023-09)

Readme was updated too and some minor fixes were taken.

## [2.1.19] 2023-03-14
New validation rule "checkHex" for validating HEX color codes added.
Update Readme with tables containing all methods and links to a more detailed description of each method
Translation file updated
AllowDynamicProperties class added to prevent warning on PHP 8.2 for dynamically declared properties in FrontendForms.module file.
Dynamically created properties have been replaced to work properly with PHP 8.2 (Thanks to Gabriel Tenita for reporting the issue)
Dynamically created properties will lead to an error in upcoming PHP 9 version. To prevent problems in the future all dynamic properties were replaced by a declared array property, which holds all dynamic properties

## [2.1.20 -2.1.21] 2023-03-16
Some bug fixes according to the removement of dynamically created properties to make the module working without troubles at PHP 8.2 and higher

## [2.1.22] 2023-03-16
Add missing check if failed attempts should be logged or not. Before, every failed attempt has been logged independent of settings inside the module configuration.
Remove hardcoded max attempts limit set to 0. This was added only for testing purposes and I have forgotten to remove it afterwards.

## [2.1.23] 2023-03-18
Remove bug in createFilesDir() method inside FrontendForms.module. This method should copy files from FrontendForms/assets to site/assets/FrontenForms but there was a logical bug, so the files will never be copied. This bug is fixed now.

## [2.1.24] 2023-03-25
A small update of the getLangValueOfConfigField() method: By default the site language will be taken for the translation, but now a new parameter allows to add the id of a specific language. Now you can get the translation of of a static translation in every language not only in the current by adding the id of the language as parameter. This is needed for upcoming modules, but can be used in any situation if needed.

## [2.1.25] 2023-03-27
Placeholder variables for date and time does not work in other languages than in English. This issue is fixed now. The language of the current user will be taken into account and the date and time will be output in the current user language. Some other small changes were done.

## [2.1.26] 2023-03-28
Missing translations for image CAPTCHA added.
Fixing bug on label if asterisk was disabled (missing typehint added)

## [2.1.27] 2023-04-03
Fixing bug on label if asterisk was disabled (missing typehint added)
Fixing wrong date saving on password update from GitHub

## 2023-04-04
New public method getFormElements() added: This outputs a numeric array containing all elements of the form (inputs, buttons) as objects

## [2.1.28] 2023-04-18
2 new methods for positioning fields inside the form added. This is especially useful for developers, not for creating forms by hands.
addBefore(): add a field before a given field
addAfter(): add a field after a given field
Both methods check before if the field is present in the formelements array and remove it to prevent 2 instances of the same field

## [2.1.29] 2023-04-20
Correct bug in getPlaceholder() method inside form.php
Adding redirect in formvalidation.php in checkDoubleFormSubmission() method after double form submission to clear form fields
Adding new method subject() to form.php only for dev purposes for future modules (not needed in normal form creation)
Optimizing ad re-writing the add() method inside form.php. Preparing the method for upcoming FrontendContact module.

## [2.1.30] 2023-04-20
Important update: Temporary upload folder for sending attachments will not be created newly after updating the module. This leads to an error if sendAttachments() method will be used.
Now the existence of the folder will be checked before and if the folder is not present it will be created.

## [2.1.31] 2023-04-25
Fixing of 2 bugs (Thanks to pmichaelis from the ProcessWire support forum)
- Renaming of TextcaptchaFactory.php to TextCaptchaFactory.php to prevent problems between upper and lowercase letters on live server. There was a writing mistake inside the file name.
- Add missing translation string inside AbstractTextCaptcha.php
Renaming TextcaptchaFactory.php to TextcaptchaFactory.php

## [2.1.32] 2023-04-26
- Renaming showFormAfterValidFormSubmission() and getShowFormAfterValidFormSubmission() methods to showForm() and getShowForm().
- Updating readme.md with descriptions of both methods

## [2.1.33] 2023-04-26
This update contains 4 fixes for updating the module:
- upgrade() method added to update captchaimage.php in root directory too during module update
- tmp_uploads folder for temporary storage of uploaded files will be created once more if it does not exist
- content of assets folder, which contains images for HTML email templates will be copied to site/assets/files again
- The email pre-header, which will be set with the $mail->title('My pre-header text') property, will be added automatically to the email. No need to add it to HTML templates manually. By not using an HTMLl template it will be added automatically on top of the email, before the content.

## [2.1.34] 2023-05-07
- Improving markup of following email templates: template_1.html, template_4.html. Now, both templates fit better on different screen sizes
- Fixing bug in file size conversion to readable string and output under file upload field

## [2.1.35] 2023-05-08
New feature added: Now you can display a link under file input fields to clear the input via JavaScript. If you have selected a file and you want to remove it before sending it, just click on the link and the input field will be cleared.

By default, this feature is not enabled, so you have to enable it with the [showClearLink()](#showclearlink---show-or-hide-a-link-to-clear-a-file-input-field) method. If you want to customize the link to your needs you have to take the [getClearLink()](#getclearlink---get-the-link-object-described-in-the-previous-method-for-further-manipulations) method for further manipulations.

To read more about these 2 new methods go to the readme file and search for showClearLink() and getClearLink().

## [2.1.36] 2023-05-09
- Clear input field link now will be displayed by default (before it was set to false = no display), but a Javascript function was added to display the link only if a file was added to the file upload field and not to display the link every time as before.

## [2.1.37] 2023-05-16
4 new validation rules for validation of dates inspired by Andy from the PW forum added:
- dateBeforeField validator: Checks if a date is before a date entered in another field inside the form
- dateAfterField validator: Checks if a date is after a date entered in another field inside the form
- dateWithinDaysRange validator: Checks if a date is within a given time range in days depending on a date entered inside another field inside the form.
Fe date must be within a time range of 7 days starting from a date entered inside another form field. Example of time range: start date: 2023-05-15, timerange: 2023-05-15 - 2023-05-22 (7 days in the future). Value must be between those 2 dates -> between 2023-05-15 and 2023-05-22. Supports a positive (future) and negative (past) days value.
- dateOutsideOfDaysRange validator: Checks if a date is outside a given time range in days depending on a date entered inside another field inside the form.
Fe date must be after the end of a time range of 7 days starting from a date entered inside another form field. Example of time range: start date: 2023-05-15, forbidden timerange: 2023-05-15 - 2023-05-22 (7 days in the future). Value must be outside this time range -> after 2023-05-22. Supports a positive (future) and negative (past) days value.

Usage examples of this new validators can be found inside the examples folder in the field-validation.php. Please study the examples there on how to use them.

## [2.1.38] 2023-05-20
- Replacing check if page has been loaded in Javascript: document.readyState === "complete" was replaced by window.onload = function () because it does not work anymore. This check is necessary for showing and counting the seconds to the user if a form was submitted too fast.
- New Javascript functions added for the new validators (dateBeforeField, dateAfterField, dateWithinDaysRange, dateOutsideOfDaysRange) which were added in version 2.1.37. The new Javascript functions add min and/or max HTML5 attributes on the fly to the input field depending on the date value inside another field. It works by using eventlisteners and data attributes. 

The min and max attribute on date fields with the date picker prevent users from selecting dates which are not allowed.

Please note: This Javascript enhancement is primarly designed for the usage with date and datetime input fields. These fields use the ISO format for dates (YYYY-mm-dd), so please use these input types for dates.
If you are using a text field instead, you have to take care that the user enters the date in the ISO format, otherwise the Javascript functions would not work. 
So using date and datetime fields for entering dates would be the best way to go.

## [2.1.39] 2023-05-25
- New method to create password syntax pattern added to allow HTML5 browser validation for password field. If HTML5 browser validation is enabled, the requirements for the password can be checked via HTML5 browser validation pattern.
- Username validation syntax changed to only allow lowercase letters, numbers, underscore and hyphen (syntax will be taken from ProcessProfile.module)
- Create new custom folder "frontendforms-custom-templates" for adding custom email templates inside, that can be used with email sending. IMPORTANT: The folder will only be created after you have have downloaded the new version of the module and have pressed the "Continue to module settings" button. Pressing of the button forces ProcessWire to create the directory. After that you can put your custom email templates into this folder and select them inside the module configuration. If you install FrontendForms for the first time, the folder will be automatically created during the install process and you have to do nothing special. If you do not see the template inside the module configuration, please refresh the configuration page.

## [2.1.40] 2023-06-01
This update contains a bug fix concerning sending mails with custom templates. If a custom template was selected via the mailTemplate() method, the custom folder has not been checked for the template. 
Now, if a template will be selected via the mailTemplate() method, it checks for the template in the default template folder first, then inside the custom template folder and if the the template was not found it throws an exception to the user, that the template was not found.

## [2.1.41] 2023-06-06
- Declare properties title and desc in AbstractImageCaptcha.php to prevent notification in PHP 8.2
- Adding a hint to the statistic block if logging of failed attempts is not enabled. If so, then the user will be able to click on a link which opens the appropriate settings fieldset where he can enable logging. This is more user friendly.
- Change the path to the module from $this->wire('config')->paths->$this to $this->wire('config')->paths->siteModules.'FrontendForms/'. This line of code leads to problem on the FrontendContact.module

## 2023-06-09
- 3 new CAPTCHA images added

## [2.1.42] 2023-07-21
This update comes with 2 small bug fixes:

1) There was a rendering bug on input field type hidden, where the attribute *hidden* was rendered instead of *type="hidden"*. This leads to problems if you are trying to set the value via JavaScript.
2) Another bug was by setting integers to form field values, because form field values are always a string or an array. If you are trying to set an integer as the value, it will be ignored. Now a value of type number will be automatically typecasted to a string if you try to set this as an input value.

## [2.1.43] 2023-07-22
This update comes with an addition and a bug fix for path problems on certain servers caused by filenames with uppercase letters.

*1) New validation rule added*   
New validation rule for checking syntax of international names added. This rule is based on the international names regex from https://regexpattern.com/international-first-last-names/. The name of the new validation rule is [firstAndLastname](https://github.com/juergenweb/FrontendForms#firstandlastname).

*2) Fixing path problem for uploaded files*  
Thanks to pmichaelis from the support forum who reported a path problem on uploaded files, which contains uppercase letters in the file name. The uppercase letters in the file names lead to the situation that Processwire could not find this file on the live server in his case. Converting the filenames to lowercase letters only solves this problem (Thanks for providing the solution too :-).
I have fixed this bug by converting all filenames to lowercase before saving it inside the assets folder. As an addition I also use the [filename sanitizer](https://processwire.com/api/ref/sanitizer/filename/) from ProcessWire to beautify the filenames and to set a max length of 128 characters.
If you are running into troubles after this changes to the uploaded files, please let me know.

## 2023-07-24
Minor update to Forms/Textelements/Link.php

Now you will be able to add multiple querystrings to a link and not only one. The querystring property has been changed from a string to an array. Now you can use the mehod *setQueryString()* multiple times on a Link object to add multiple querystrings.
This is more interesting for Devs and does not has an impact on most of the users.

## 2023-08-13
Minor update to Forms.php

There was a problem of the Captcha position if you are using hidden fields after the submit button. If a hidden field will be added after the submit button, then the CAPTCHA will be added after the submit button and before the hidden field, but it should be inserted always before the button element. This is a rare case scenario, but it happened in my case. 
I have corrected this little bug by ignoring all input fields of the type hidden in the input fields array. The last visible form element should be the button. If you are adding some hidden fields after the button, it does not matter any more and the CAPTCHA will stay before the button element.

## 2023-09-02
Some new images for image captcha added. All captcha images reduced in size to 900x600px to reduce storage space.

## [2.1.44] 2023-09-04
Get rid of usage of AllowDynamicProperties class

PHP 8.2 complains about undeclared properties. To prevent the display of the warning messages, the AllowDynamicProperties class was used, which is a work around for this issue. In PHP 8.3 undeclared properties will lead to an error and not to a warning. To prevent unwanted surprises in higher PHP versions, all undeclared properties will be declared and the usage of the AllowDynamicProperties class will be removed.

As written before some new CAPTCHA images have been added and the query string method has be modified too.

## [2.1.45] 2023-09-07
Correcting rendering bug of email template if there are multiple forms on 1 page.

I have discovered that if you are using more than 1 module that derives from the FrontendForms module on the same page (fe FrontendContact and FrontendLoginRegister), than the rendering of the email template takes place multiple times before the email will be send, so that the markup of the HTML of the template will be broken. 
I could not figure out the cause of this problem, but I found a way to prevent this behaviour by using a session to check if the template have been rendered or not.
The session prevents the rendering of the template once more on the next run, so it will only be rendered once instead of twice or multiple times.
After the mail has been sent, the session will be deleted.
As always, please test the mail sending process, if it works as expected and post possible issues here.

## [2.1.46] 2023-09-11
Fixing bug on calculating the wrong position of CAPTCHA field inside the form.

## 2023-09-11
According to the wish of an user, an additional wrapper has been added arround the input field of the CAPTCHA with the class"captcha-input-wrapper".
This addition has no effect for others.

## [2.1.47] 2023-10-09
Now FrontendForms supports Ajax form submission!

Ajax form submission prevents a page reload after the form has been submitted. This could be useful in scenarios, where you do not want a reload (fe if your form is inside a modal box or inside a tab) after the form has been submitted.
You can disable/enable Ajax submission by checking a checkbox inside the module configuration, or you can overwrite the global value by using the setSubmitWithAjax() method on per form base.

If you are enabling this feature, a progress bar will be displayed after you have pressed the submit button to inform the user, that the form will be validated now. Otherwise, the user will not see any action until the validated form will be loaded back into the page.
If you do not want to show the progress bar, you can disable it inside the module configuration too. 
With the showProgressbar() method, you can overwrite this global setting on per form base.

In the case, you want to redirect the visitor to another page, after the form has been submitted successfully, you cannot do this via a PHP session redirect, because the form has been submitted via Ajax.
In this case a JavaScript redirect has to be done. To force a JS redirect after the submission, you need to use the setRedirectUrlAfterAjax() method. Put the new URL inside the parenthesis, and the user will be redirected to this URL after the form has been validated successful.

You will find a more detailed information about these 3 new methods here: https://github.com/juergenweb/FrontendForms/blob/main/README.md#setsubmitwithajax---use-ajax-for-form-submission

## [2.1.48] 2023-16-09
This version comes with some fixes and a new addition

The previously introduced support for Ajax form submission leads to a problem if you use the setRedirectAfterAjax() method in combination with an internal anchor (fe the $form->setRedirectAfterAjax($page->url.'?#myanchor).
It has worked well without the anchor in the URL, but adding an anchor blocks the correct rendering of the form and the output of the success message afterwards.

To prevent this behavior, I have written a work-around in JavaScript: an internal anchor will be removed and re-written to a query-string (fe "#myanchor" will be re-written to "?fc-anchor=myanchor"). In this case, the rendering of the form after the redirect works without problems.

The anchor will be added afterwards via JavaScript and the page jumps to the given anchor. 
Visitors will not see any difference to the normal way. The only difference is that you have an additional querystring inside the browser bar. That's all, but now you can also use internal anchors without problems. 

The second change is the addition of a character counter for textareas.

If you want to restrict the numbers of characters that could be added inside the textarea, you have to use the "lengthMax" validator including the value of the max allowed characters.

This is fine, but the visitor will not know how many characters he has left until he reaches the limit of numbers. For this case scenario, you can add a new method to every textarea field: [useCharacterCounter()](https://github.com/juergenweb/FrontendForms/blob/main/README.md#usecharactercounter---add-a-reverse-character-counter-below-a-textarea-if-maxlength-validator-is-set).

This method will add a character counter under the textarea which shows how many characters can be entered until the limit is reached.

## [2.1.49] 2023-10-22
Small update that fixes the permanent display of the toggle checkbox to hide/show the entered password inside a password field (Thanks to dynweb for reporting this issue).

The problem was, that the checkbox was always visible next to the password field, independent if the showPasswordToggle() method was used or not.

To keep backwards compatibility for other user, I have set the display of the checkbox by default to true and you have to manually disable it by setting showPasswordToggle(false) to false. This will not confuse other user, because the checkbox is still there after the update. Otherwise it would not be longer visible after the update until you enable it manually. 

## [2.1.50] 2023-10-27
Added a new class to create a text for accepting the privacy policy without using a checkbox.

Thanks to Chris-PW from the support forum for pointing out to me that an "Accept our Terms and Privacy Policy" checkbox in contact forms is no longer needed and recommended (especially in Europe).

Here is the link to a lawyer firm: [https://www.e-recht24.de/dsg/12687-kontaktformular.html](https://www.e-recht24.de/dsg/12687-kontaktformular.html).

For legal reasons, using a checkbox to accept the terms of use and privacy policy is not the way to go, as the user has to take action. It is better to use just a text before the submit button to indicate that the user accepts the privacy policy when filling out a contact form.

For this reason, I created a new class "PrivacyText" in /site/modules/FrontendForms/Formlements/Textelements/Defaults/PrivacyText.php.

This class simply creates a predefined text ("By submitting this form, you agree to our Terms of Use and Privacy Policy.") that can be added to the form simply by adding the following lines of code:

```php
$acceptText = new \FrontendForms\PrivacyText();
$form->add($acceptText);
```

The best place for this text would be before the submit button element.

You can also find this new class implemented in the [default examples](https://github.com/juergenweb/FrontendForms/blob/main/Examples/default-inputs.php#L91) file.

It is up to you whether you use the "Privacy Accept" checkbox or the new PrivacyText class.

## [2.1.51] 2023-10-28
3 new methods for the form object have been added and a new functionality to position the privacy checkbox or privacy text always next to the submit button.

This update consists of 3 new public methods, which will be used by the new functionality to add the "Accept the privacy checkbox" or the "Accept the privacy" text always before the submit button.

This functionality was designed, because til now, this problem has especially occurred, if CAPTCHA was enabled.

The Captcha will be added automatically as the last element before the button element. This leads to that the privacy fields being always above the CAPTCHA and not next to the submit button, where they should be.

For this reason, I have integrated this functionality. So this new functionality shifts all privacy fields (checkbox and/or text) to the last position, independent of the last position.

The 3 new methods that were created for this functionality are public methods and can also be used for other cases if needed.

* [removeMultipleEntriesByClass()](https://github.com/juergenweb/FrontendForms/blob/main/README.md#removemultipleentriesbyclass---delete-all-instances-of-a-form-element-of-a-given-class-except-the-last-one) - delete all instances of a form element of a given class except the last one
* [formContainsElementByClass()](https://github.com/juergenweb/FrontendForms/blob/main/README.md#formcontainselementbyclass---check-if-the-form-object-contains-at-least-one-form-element-of-the-given-class) - check if the form object contains at least one form element of the given class
* [getElementsbyClass()](https://github.com/juergenweb/FrontendForms/blob/main/README.md#getelementsbyclass---get-array-of-all-form-elements-of-the-given-class) - get array of all form elements of the given class

## [2.1.52] 2023-10-31
Only some small bug fixes and code formatting optimizations taken

## [2.1.53] 2023-11-02
Fixing bug in calculation of privacy field position.

In update 2.1.51, a new functionality to position privacy fields always before the button element, was introduced. Unfortunately, there was an error copying this new functionality to GitHub. Locally, I have the changes on my computer, but for some reason, I have not copied it to GitHub.
The result was a wrong position calculation of the privacy fields in certain cases. This update should fix this behavior and now the position of the Privacy fields should always be before the button element.

## [2.1.54] 2023-12-13
New static property $framework added. This property is needed for a new module that I am working on. It outputs the framework choosen in the backend configuration on the frontend.
The property is static, so you have to call "FrontendForms::$framework" which leads fe to "uikit3","bootstrap5" or "none"

Modifiying the addAssets() method to support the new FrontendComments module, which will be published in the future. This method now adds stylesheets and JavaScript files from this new module on demand to the frontend (only on pages, where a comment field is added).

Modifying the frontendforms.js file to support star-rating of the new module on Ajax requests too (This is also for the new module).

New Hook function inside ready() added to move a comment field (if present) inside a new tab called "Comments" in the backend. By default, the comments field is located inside the content tab (as all other fields), but moving it to an newly created extra tab seems to be a better approach.

At least: The JavaScript maxCharsCounterReverse() function which is responsible to count the characters inside a textarea, if the character-counter is enabled, will be re-written to run only on textareas, which contain the data-attribute "data-charactercounter" and the value "1" and not on every textarea on the page.

## [2.1.55] 2023-12-28

Bug fixed on min time configuration input field (name attribute was missing)

Static function secondsToReadable() will be reverted to non-static. The static function leads to some problems under certain condition, so the best way was to revert it back to non-static (For more information please read the discussion in the support forum: https://processwire.com/talk/topic/26015-frontendforms-a-module-for-creating-and-validating-forms-on-the-frontend/?do=findComment&comment=238088

IMPORTANT: This has an impact on the FrontendLoginRegister module if you are using 2-factor-authentification, because this module uses the static function.
If you are using the FrontendLoginRegister module, please update this module first (or at the same time with FrontendForms) to prevent problems.

Thank you!

## [2.1.56] 2024-01-10

- Missing declaration for property $toplabel of type Textelement inside InputCheckboxMultiple.php added
- Change CAPTCHA label for CAPTCHA validation message to fit better
- New method setRedirectURL() added:
  
  This method forces a redirect after the form has been validated and the code between the isValid() condition has been executed. This can be used to redirect
  fe to a "Thank you" page after a contact form has been submitted ([read more](https://github.com/juergenweb/FrontendForms/blob/main/README.md#setredirecturl---redirect-to-another-page-after-form-submission)).
  
  It does not matter in this case if you are submitting the form via Ajax or not.
- New alias method useAjax() added: This method is an alias method for the setSubmitWithAjax() method.

  The old method forces a form submission via Ajax. The name of the old method is too long, so I decided to create a method   with a better and shorter name.
  
  So the old method still works, but for the future it will be better to use the new useAjax() method instead ([read more](https://github.com/juergenweb/FrontendForms/blob/main/README.md#useajax---use-ajax-for-form-submission)).

## [2.1.57] 2024-01-16
This update contains some new methods for support for forms in iframes on other domains and some internal methods for selecting custom modules for sending mails (for future usage in other modules based on this module):

**New method useCSRFProtection(true/false) added:**

With this method, you can enable/disable the CSRF-Protection on per form base if you want. This method is integrated in the next method and has been created to be able to use a form inside an iframe on another domain. Otherwise the form submission will not work.

[read more](https://github.com/juergenweb/FrontendForms/blob/main/README.md#usecsrfprotection---enabledisable-csrf-protection-on-form)

**New method useFormInCrossDomainIframe() added:**

This method is the method, you need to use, if you want to use the form in an iframe on another domain (not on the same domain). The problem is that forms are using sessions to work and you cannot get this session values if you are integrating a form inside an iframe on another domain.

This method disables CSRF-protection, the check for double form-submission and the CAPTCHA if set. Each of them uses sessions and cannot be used inside an iframe. If they will not be disabled, the form submission will not work.

So using this method on the form object makes it possible to use a form inside an iframe on another domain. If the iframe is on the same domain, everything works as expected and you do not need to use this method. So this method is only for the rare case of crossdomain iframe usage.

[read more](https://github.com/juergenweb/FrontendForms/blob/main/README.md#use-forms-in-iframe)

**Future preparation for using 3rd party mail modules**

The last additions are only for internal technical reasons and not for users. Modules which send mails and are based on Frontendforms will get the possibility to use a 3rd party ProcessWire module for sending emails instead of using only the default WireMail class.

For this reason some new static methods have been added, which are only for usage in other modules to make this possible.

## [2.1.58] 2024-01-16
**New validation rule added: uniqueFilenameInDir**

This new validation rule checks if a newly uploaded file has the same filename as a file inside the destination directory.

In other words: If the filname of the uploaded file is textA.txt and inside the destination directory exists a file with the same name, this validator returns an error.

Usage: $uploadfield->setRule(uniqueFilenameInDir); // Returns true or false

So this validator is only useful, if you are storing files inside a folder. It is useless if you are sending files as attachements.

As an addition you can set a parameter to force an overwrite of duplicate filenames.

Usage: $uploadfield->setRule(uniqueFilenameInDir, true); // Returns always true but overwrites existing filenames

If you add true as the second parameter, ever filename duplicate will be overwritten by adding the timestamp after the filename to make the filename unique.

Example: testfile.text will be overwritten fe to testfile-95846567.txt if it exists inside the destination directory.

In this case the validation rule returns always true.


**Bug in getValues() method fixed**

By default, the getValues() method has returned the original file names of uploaded files, not the sanitized one as stored inside the filesystem.

Fe uploaded filename "Testfile.txt" has been sanitized to "testfile.txt", but inside the getValues() result the original filename "Testfile.txt" has been displayed.

This could lead to problems if you are using this filename for other purposes, because it is not identical to the filename as stored inside the system.

I have fixed this problem now to always show the "real" filename as stored inside the file system.

## [2.1.59] 2024-01-19

**Support for WireMailSmtp added**

This minor update is only useful for users of my other modules FrontendLoginRegister and FrontendContact.

In addition to the last added feature of sending mails with the Postmark mail service, this little update brings support for sending mails with the PW-module WireMailSmtp to the children modules.

## [2.1.60] 2024-01-21

This update comes with 2 small bug fixes and one addition.

**Return value on addHookMethod for "mailTemplate" changed (FrontendForms.module)**

The custom WireMail method mailTemplate had a wrong return value and that was the reason, why this method was not chainable like all other WireMail methods. This has now been fixed.

**Update renderTemplate() method (Form.php)**

Previously, the default email template setting, as set in the module configuration, was added to any email where the email template setting was not set (null). This is not intentional. Now, if no email template setting has been made, no email template will be used.

**Support for sending mails with the WireMailPHPMailer module**

This new version includes some code changes and a new method to support FrontendLoginRegister and FrontendContact to send mails with the WireMailPHPMailer too. 
The WireMailPHPMailer comes with own property names and ist therefore not compatible with other mail classes. That is the reason, why some code changes are necessary.

## [2.1.61] 2024-02-03

- **New CAPTCHA images added**

  Some new animal and tree images have been added.

- **Support for setting custom path inside mailTemplate() method added**

  Until now the WireMail method mailTemplate() has only supported the name attribute of a given HTML email template     including the extension HTML.
  
  `$mail->mailTemplate('myTemplate.html');`
  
  Due to a user request from Donatas, where he asked for support of adding a path to a custom template folder to this method too, I have added this feature now.
  
  Usecase: If you do not want to use the default custom template folder under */site/frontendforms-custom-templates/*, you can now add a path to another folder including the name of the template.
  
  Example: 
  
  Your new folder is located under */site/assets/mycustomfolder/* and the name of your email template file is called *myNewTemplate.html*.
  
  Now you can add a complete path tho the method too: 
  
  `$mail->mailTemplate($config->paths->site.'assets/mycustomfolder/myNewTemplate.html'); // add this to the mail object` 
  
  The given template from this custom folder will be used to send the mail.

## [2.1.62] 2024-02-05

This update comes with mail sending modifications only: A general addition is to use placeholders without using an email template and special additions for sending mail with the PHPMailer class.

- **Use placeholders even if you do not use an email template**

  Placeholders have been designed for use with HTML mail templates, but it was not possible to use them without an HTML template until now. This is now fixed, and you can use placeholders even if you set mailTemplate() to *none*.

  If you do not know what placeholders are and how they can be used inside the mail body, take a look inside the [docs](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#placeholder-variables-for-usage-in-email-templates).

- **Problem on sending attachments if WireMailPHPMailer is used is fixed now**

  This module supports sending mail via 3rd party mail services, but I have discovered a problem with sending attachments if you are using the WireMailPHPMailer module.

  This module has its own property names, and therefore a problem occurs when sending attachments. This problem is fixed now. By using other supported 3rd party modules, there is no problem.

  To show how to send mail via WireMailPHPMail, WireMailSMTP and PostmarkApp I have added 3 new example files inside the [Examples folder](https://github.com/juergenweb/FrontendForms/tree/main/Examples). These files contain full-functionable contact forms, including a file upload field by using these external modules.

  - [Contact form using SMTPMailer](https://github.com/juergenweb/FrontendForms/blob/main/Examples/contactformWithWireMailSMTPModule.php)
  - [Contact form using PostmarkApp](https://github.com/juergenweb/FrontendForms/blob/main/Examples/contactformWithWireMailPostmarkAppModule.php)
  - [Contact form using WireMailPHPMailer](https://github.com/juergenweb/FrontendForms/blob/main/Examples/contactformWithPHPMailerModule.php)
 
- **Making some custom methods chainable**

    The following custom methods for the mail object are now chainable: $mail->title, $mail->sendAttachment() and $mail-mailTemplate().

- **Problem on custom path setting fixed if the mail template has been set outside the isValid() method**

  I have discovered a problem if you are using more than one form on a page and you have set a custom template path and the $mail-mailTemplate() method has been written outside the isValid() method (fe inside the constructor). This has been led to an error, but it is fixed now.

## [2.1.63] 2024-02-10

- **Bug inside getValues() method in Form.php fixed**

  There was a little bug concerning uploading files at the foreach loop inside this function. There was a missing differentation between single and multifile upload, which leads to an error if a single file (string) has been passed to the foreach loop, which needs an array as value and not a string.

- **New method addHorizontalRule() to the "Select" class added**

  As pointed out in the latest ProcessWire news "[InputfieldSelect and InputfieldSelectMultiple now accept horizontal rules](https://weekly.pw/issue/508/)" I added this feature now to select inputs of FrontendForms too.

   You can read more about this new browser feature [here](https://developer.chrome.com/blog/hr-in-select).

  *Short description:*

  Now, you can add "hr" (horizontal rule) elements into the list of select options and they will appear as separators to help visually break up the options for a better user experience.

  For more information and how to use it inside FrontendForms, please read the description inside the [docs](https://github.com/juergenweb/FrontendForms/blob/main/README.md#addhorizontalrule---add-a-hr-tag-to-select-input-fields-to-help-visually-break-up-the-options-for-a-better-user-experience).

## [2.1.64] 2024-02-18

- **2 new CAPTCHA images added**
- **New feature to render the field description on different positions added**
  
  Due to a user request to add the possibility to change the field description on demand before the input field I have added this feature now.

  You can position the description now to the following 3 positions:

  * before the label
  * after the label
  * after the input field (default)
 
  You can make your settings globally inside the module configuration, on per form base or individually directly at each input field. With these options you have the largest flexibility to render your field descriptions in your desired position on each input field.

  2 new additional methods have been added:

  * [setDescPosition()](https://github.com/juergenweb/FrontendForms/blob/main/README.md#setdescposition---change-the-position-of-field-descriptions): With this method you can overwrite the description position on **per form base**
  * [setPosition()](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#setposition---change-the-position-of-field-description-directly-at-the-input-field): With this method you can overwrite the description position **on the input field directly**
 
    The recommended way ist to set your prefered description position inside the module configuration first. You will find this new configuration field under *Markup and styling settings for the forms -> Settings for the markup and styling of the form and the input fields -> Set the position of the field description*

    Afterwards you can use the previous mentioned methods to overwrite the global settings.

## [2.1.65] 2024-02-22

- **Change CAPTCHA font-family path from absolute to relative**

  Thanks to Donatas from the PW-support-forum for reporting the issue, that the paths for the font-family files have been strored as absolute paths inside the database. This leads to problems, if you are migrating your site (fe. with RockMigration) to another server, because the font-files cannot be found any longer. The reason for this is that the paths are no longer valid after the migration.

  I have fixed this problem now, by changing the code a little bit to store relative paths instead of absolute paths for the font-files inside the database.

  **IMPORTANT:**

  The following steps are neccessary, if you update the module (not necessary on a fresh install):
  
  To save the relative paths, please press the "Refresh all fontfiles" button below the Font-select input field first and then press the save button of the module configuration page to save the configuration data once more. Afterwards the relative paths are stored inside the database.
  
## [2.1.66] 2024-04-02

- **16 new CAPTCHA images added**
  
  2 new tree images, 6 new house images, 1 new car image, 3 new animal images and 4 new flower images for usage with the image CAPTCHA option have been added.

- **Change method addUnitToLabel() from protected to static public**
  
  To be able to use this methods in other modules too, which are based on this module, I have changed the method now from *protected* to *public static*. This change has no effect for users of this module, it is only an improvement for me as developer ;-), so I can use this method in other modules too without duplication of code.

  
- **Support for FrontendComments added**
  
  I have added support for upcoming FrontendComments module to inject the CSS and JS files of this new module on demand, if this module is installed. At the moment, I do not know when I publish this module. It needs more testing and fixing some bugs. This addition is only for dev purposes and has no effect to FrontenForms users.

- **CAPTCHA image width set to max value of 900px**
  
  The CAPTCHA images provided by this module have a size of 900x600px. In the module configuration you can set the size of the CAPTCHA image by adding a width value. To prevent setting a width larger than 900px, I have added a max value of 900px to this configuration input field. If you try to set a higher level an error message will be displayed, that a higher value than 900px is not allowed.

- **Missing closing div tag added**
  If you are using the image captcha, then you can run into layout problems, because a closing div tag has not been rendered. This bug is fixed now.

- **Remove trailing slash from CSS link**
  The trailing slash from the CSS-link has been removed, because it is no longer valid and the HTML validator complains about it (Message: Trailing slash on void elements has no effect and interacts badly with unquoted attribute values).

- **Remove wrong for attribute from CAPTCHA label**
  There was a wrong for attribute at the top label of the CAPTCHA, which has been removed now.

## [2.1.67] 2024-04-17

- **2 new CAPTCHA images added**
  
  1 new tree image and 1 new flower image for usage with the image CAPTCHA option have been added.

- **"Defer" attribute to Javascript tag added**
  To prevent blocking on rendering, the defer attribute has been added to the FrontendForms Javascript tag.

  - **Time querystring from CSS and JS link removed**
  To be able to cache these static resources, the time query string parameters have been removed from the file path.

 - **Bug in getTime() and getDate() method fixed**
   If you have installed the language module, but you use only a single language (not multilanguage), then the date and time format for emails as set inside the module configuration will not be taken into account. This bug is fixed now and the configured date and time format will be used to format date and time strings inside email templates.

 - **New default inputfield "Phone" added**
   In addition to other pre-defined input field a new inputfield for adding a phone number has been added. You will find it inside Formelements/Inputelements/Inputs/defaults.

## [2.1.68] 2024-04-18

This update contains some little additions, which are necessary for the next update of the FrontendContact module, which contains 2 new fields with JS animations (show/hide). 

- **New method useCustomWrapper() added**
  
  This new method adds a custom wrapper to an inputfield, and will be used for showing and hiding the new phone field in the upcoming update of the FrontendContact module via Javascript.

 - **Some Javascript functions added**
  The frontendforms.js got also some new functions for the FrontendContact module. Due to performance reasons I have added this JS code to the frontendforms.js instead of loading an extrea JS file for the FrontendContact module.

## [2.1.69] 2024-05-05

- **5 new CAPTCHA image added**
  
  1 new animal image and 4 new mountain images for usage with the image CAPTCHA option have been added.

- **Bug with missing IP in modal fixed**

Thanks to Andy from forum, who reported this issue under https://processwire.com/talk/topic/26015-frontendforms-a-module-for-creating-and-validating-forms-on-the-frontend/page/8/#comment-241264

There was an error by trying to view the detail page, who results from a missing IP inside the modal. This error is fixed now.

## [2.1.70] 2024-05-18

- **Display warning if root is not writeable**
  
The captchaimage.php will be installed inside the root directory, but this is only possible, if this directory is writeable. If not, the user will get a warning now with further instructions on module install. Until now, the user has not been informed about this problem. 

This is especially the case if a user sets the file permission on the root directory to "read only" for security reasons.

Special thanks to Andy for reporting this issue.

- **Support for Pico CSS framework added**

The module has been updated to support Pico CSS framework beside Uikit and Bootstrap. This is the 3rd framework that is supported by this module.

You will find more information about Pico at https://picocss.com/.

In detail, the error message, the success message and the field notes will be displayed with tag "small" in this case and the appropriate Aria attributes will be added to these items too.

Thanks to Andi from the support forum for informing me about this framework.

- **New method for form fields added**

I have added a new method for an inputfield: setSuccessMessage()

This is the opposite of setErrorMessage() and outputs a text after form submission if the value entered inside an inputfield is valid. 

For example a user enters a correct value inside an inputfield, then a success message like "This value is ok" can be displayed after form submission under the input field.

I do not think so that a lot of users will use this possibility, but if you want...you have the possiblity now.

This new addition was inspired by the Pico CMS framework, which also displays an indication of valid fields.

To use this method, please add it to the inputfield in the way like this:

$myField->setSuccessMessage('This value is correct');

You will find more information at https://github.com/juergenweb/FrontendForms/blob/main/README.md#setsuccessmessage---display-a-message-after-form-submission-and-the-entered-field-value-is-valid

## [2.1.71 + 2.1.72] 2024-05-18

- **Bug on displaying success message without value fixed**
  
The new method setSuccessMessage() has displayed the success message even no value has been entered in the inputfield. This has been fixed now and the message will only be displayed if the user has entered a value.

The aria attributes aria-invalid and aria-errormessage will be also not added if the there is no value and no error.

## [2.1.73] 2024-05-20

- **Aria attributes added for better accessibility**

To make the form more accessible for screen readers, I have added the following ARIA attributes, which are automatically added to the corresponding form elements:

* aria-selected: will be added to selected option elements
* aria-describedby: will be added to the inputfield and links to the description, notes and success messages
* aria-invalid: will be added to the input field with status true or false after form submission
* aria-errormsg: will be added to inputfield to link to the error message element

I think these are all Aria attributes that are not covered by semantic HTML elements, but if you think something is missing, please post your suggestion in the support forum.

*Example:*

```html
<div class="fieldwrapper" id="inputfieldtest-php-fieldwrapper">
  <label class="label required" for="inputfieldtest-php">My preferred PHP version is<span class="asterisk">*</span></label>
  <div class="inputwrapper" id="inputfieldtest-php-inputwrapper">
    <select id="inputfieldtest-php" name="inputfieldtest-php" class="select" required="" aria-invalid="false" aria- describedby="inputfieldtest-php-successmsg inputfieldtest-php-desc">
    <option value="">-</option>
    <option value="PHP 6" selected="" aria-selected="true">PHP 6</option>
    <option value="PHP 7">PHP 7</option>
    <option value="PHP 8">PHP 8</option>
    </select>
  </div>
  <p class="description afterInput-desc" id="inputfieldtest-php-desc">Description for this select field</p>
</div>
```

As you can see, a lot of ARIA attributes will be added to the markup.

- **Correct Markup of radios and checkboxes for Pico CMS framework**

When using checkboxes and radios, error and success messages must be inside the label tag if you are using the Pico framework. This is an unusual display, but this is how Pico has defined its markup. 
Until now, the error and success messages were displayed below the input field, but not within the label tag. For the Pico markup, I have now corrected this and the markup is now displayed as described in the Pico documentation.

- **Form markup updated to Bootstrap 5.3**

I have discovered some changes from previous versions of the Bootstrap framework until now. Now I have added some additions to make the rendered form markup working with Bootstrap 5.3 CSS framework.

## [2.1.74] 2024-05-22

- **Bug fix for useAriaAttributes() on text elements**

This is only small bugfix, to prevent the running of the new useAriaAttributes() methods on non input elements which leads to an error. Thanks to elvina from the support forum for reporting this issue.

## [2.1.75] 2024-05-23

This version comes with a new CAPTCHA and 2 new methods and 1 new validator.

- **Simple question CAPTCHA added**

As the name mentioned, this CAPTCHA contains a simple question, that the user has to answer. It does not contain pre-defined answers as fe. radio buttons. Instead, the user has to write the answer into a text input field (free text), which makes it extremly difficult for SPAM bots to solve this CAPTCHA.
This means, that you have to define all possible answers in an array. Please note: Make the question as simple as possible.

Thanks to Andi from the support forum, for this suggestions. Andi has a lot of experience with this kind of CAPTCHA over the years and had no problems with SPAM. Now this type can also be used with FrontendForms.

Please select the new CAPTCHA in the module configuration and enter a simple question and the according answers and your done.

You get a little bit more information about it here: https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#simple-question-captcha

- **1 new method for this "Simple question CAPTCHA" added**

By default, a single question and the appropriate answers will be defined in the backend.

To be more flexible, you can overwrite the question and the answerse with a new method on per form base: **setSecurityQuestion()**.

More information about it: https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setsecurityquestion---change-the-question-and-answer-of-a-question-captcha-on-per-form-base

- **1 new method for all CAPTCHAs added**

As introduced in FF 2.1.70, you can add a success message in addition to error messages to every input field. The CAPTCHA field is a system field in this case and is not reachable for manipulation by the user. To be able to add this success message to the CAPTCHA field too, I have added this new method: **setCaptchaSuccessMsg()**. 

More information about it: [https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setsecurityquestion---change-the-question-and-answer-of-a-question-captcha-on-per-form-base](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setcaptchasuccessmsg---add-a-success-message-under-the-captcha-field)

- **1 new validator added: compareTexts**

As the name mentioned, this validator is for checking a piece of text against different texts. It was primarly designed for the simple question CAPTCHA, but it can also be used for all other text input fields too.

More information about it: https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#comparetexts

## [2.1.76] 2024-05-23

This is a small update to fix 2 small bugs.

* The first bug was a copy and past mistake. If you have chosen the image captcha, the random text captcha has been displayed instead, because the wrong class name was copied. This is fixed now.
* The second small bug was a missing translation in the captcha radio select for the image captcha. Now the translation will be displayed.

## [2.1.77] 2024-05-24

- **Add requiredIf condition to simple question CAPTCHA config fields**

The fields to enter a simple question and several accepted answers were set to required only. This has been changed now to **requiredIf** this CAPTCHA has been selected. Otherwise they are not required any longer.

- **Missing display of password requirements fixed**

Inside the security tab for the password, the password requirements, which has been set, were not displayed. This has been fixed now.

- **New method added to be able to change the CAPTCHA position inside forms**

The CAPTCHA is a so called "system field", which means, that it will be added by the module and not by the user. By default, the CAPTCHA will be placed before the submit button. This is fine in most cases, but if you want to position the CAPTCHA on another position, there were no possibility until now.

With the new method setCaptchaPosition(), you are now able to position the CAPTCHA on every position inside the form by defining a reference field and the position attribute "before" or "after".

The method must be applied to the form object and must contain as first parameter the name of the reference field (fe. email) and as second parameter the attribute "before" or "after".

``$form->setCaptchaPosition('email', 'after');``

This example places the CAPTCHA **after** the field with the name attribute "**email**".

## [2.1.78] 2024-05-27

This update comes with 1 bug fix, 2 JavaScript additions, 7 new general customizing methods for all CAPTCHAS and 1 special new method for the simple question CAPTCHA.

- **The entire JavaScript code was placed in a closure to avoid conflicts.**

  To avoid future conflicts with other JavaScript files, I have placed the entire JavaScript code in a closure that works like a namespace. This prevents conflicts with other JavaScript files that use variables with the same names as those in this file.

- **JavaScript addition to submit timer function added**

  If validation min time until a form will be submitted is enabled, you will get a warning if the form has been submitted to fast. In this case a warning alert will be displayed and a reverse timer counts from the min time (fe 5 seconds) to 0. After this time you will be able to submit the form again.
  
  If the counter was finished, it has been removed, but the alert was still there.
  
  I have added a little piece of code, so that the warning alert will be removed too after this time period, because it is no longer relevant for the user.


- **Missing rendering of field description at the end of inputfield re-added**

  If the description position was set to "afterInput", then the description tag has not been rendered, because the     rendering method was missing. This bug is fixed now.
  
  The new methods afterwards are especially for customizing the display of the CAPTCHA input field to your needs. The CAPTCHA input field is a so called "module system field", which means it will be created and rendered by the module and will not be created via API by the user.
  
  This is the reason, why it cannot be customized like other fields and therefore it needs some special methods to be more flexible in customizing the output.

- **New method setCaptchaErrorMsg added**

  This method is for overwriting the default error message for the captcha validation, that will be displayed if the value entered is not correct. 
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setcaptchaerrormsg---overwrite-the-default-error-message-of-the-captcha-validation-rule)

- **New method setCaptchaRequiredErrorMsg added**

  This method is for overwriting the default error message for the captcha required validation, that will be displayed if the value is missing. 
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setcaptcharequirederrormsg---overwrite-the-default-error-message-of-the-captcha-required-validation-rule)

- **New method setCaptchaPlaceholder added**

  This method is for adding a placeholder text to the captcha input field.
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setcaptchaplaceholder---add-a-placeholder-text-to-the-captcha-input-field)

- **New method setCaptchaNotes added**

  This method is for overwriting the default notes, that will be displayed under the input field.
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setcaptchanotes---overwrite-the-default-notes-of-the-captcha-input-field)

- **New method setCaptchaDescription added**

  This method is for adding a description text to the CAPTCHA field, if needed.
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setcaptchadescription---add-a-description-to-the-captcha-input-field)

- **New method setCaptchaDescriptionPosition added**

  This method is for positioning the description text of the CAPTCHA field independently, if needed.
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setcaptchaposition---place-the-captcha-on-a-position-of-your-choice-inside-the-form)

- **New method setSimpleQuestionCaptchaRandomRotation added**

  This method is for the simple question captcha only and offers the possibility to display several questions randomly instead of only one question. The questions can be set as an array and are highly configurable.
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#setsimplequestioncaptcharandomrotation---showing-multiple-questions-randomly-instead-of-a-single-question-with-the-simple-question-captcha)

- **New method removeCaptchaLabel added**

  This method is for removing the label from the CAPTCHA field. Optional you can choose if you want to display the label text as placeholder text instead. 
  
  More info [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#removecaptchalabel---hide-the-label-and-display-the-label-text-optionally-as-placeholder-text)

## [2.2.0] 2024-06-14

This update is a bigger update and comes with 1 new admin module and 2 new methods for the simple question CAPTCHA for adding questions per API.

- **New admin module FrontendForms Manager**

  This is a new UI for entering questions for the SIMPLE QUESTION CAPTCHA directly in ProcessWire. If you are not using this new CAPTCHA type, this module may be not relevant for you at the moment, but it should be extended with further functionality in the future.

  The module will not be installed automatically during the update. You need to go to the module manager, click the refresh button and install this module manually.

  Please read the full description and instruction for this module [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#custom-module-frontendformsmanager).

  To all users who run a multilanguage site: If you are importing the German language files which are delivered with this module, please take care that you have **2 language files** now instead of one (because there are 2 modules):

  * de-frontenforms.csv (for the FrontendForms module)
  * de-frontendformsmanager.csv (for the new FrontendForms Manager module)

So please take care to import the correct translation file into your module.

 - **2 New methods for adding new questions to the CAPTCHA**

   The first method is not completely new, but I want to mention it: **setSecurityQuestion()**.

   This method is for adding a single CAPTCHA question on per for base to the CAPTCHA.

   More info [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#setsecurityquestion---enter-only-a-single-question).

   The other method **setSecurityQuestions()** - plural - is the replacement of the earlier integrated setSimpleQuestionCaptchaRandomRotation() method, which is deprecated now and has been replaced by this method.

   This method is for adding multiple questions at once on per form base to the CAPTCHA.

   More info [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#setsecurityquestions---enter-multiple-questions-at-once).

   If you are using the new FrontendForms Manager module, you do not need to use these 2 methods (but you can to add additional questions).

   These 2 new methods are the API equivalent to the FrontendForms Manger, so you can use multiple questions too, without using the new module.

   So it is up to you, if prefer to use the FrontendForms Manager or you want to use the API methods or you want to combine both.

## [2.2.1] 2024-06-18

 - **New CAPTCHA images added**

   3 new Flower images for usage with the image CAPTCHA added.

- **Limit questions in questions array to 25 items to prevent overhead**
  The getCaptchaQuestions() method returns a page array with all questions that have been added via the FrontenForms Manager. This is fine if the number of questions is not to high. If there is the case that you have entered for example 100 questions, than the questions array contains 100 items, which is very large. To prevent such a large array, the max. number of questions in the question array will be limited to 25 question now. This 25 quesitons will be selected randomly.
  
- **Check for installation of LanguageSupportFields added**
  A problem has been reported, which occurs during the installation of the FrontendForms Manager (SQL error during the creation of ProcessWire fields).

   After several tests I figured it out: The problem occurs during the creation of multi-language fields if the module "LanguageSupportFields" was not installed, so I added a check to check if this module is installed. If yes, then multi-language fields will be created, if not, then single language fiels will be created.

  This problem occured by the creation of *FieldtypeTextareaLanguage" and *FieldtypeTextLanguage*, but should be solved now.

## [2.2.2] 2024-06-20

- **Bug at asterisk rendering at Pico framework fixed**

  The asterisk at a checkbox has been displayed independently if the checkbox was required or not (only at Pico framework). This bug has been fixed now

- **New validator checkIban added**

  This validator checks if an IBAN entered in an inputfield has the correct format ([more](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#checkiban)).

- **New validator checkBic added**

  This validator checks if a BIC entered in an inputfield has the the correct format ([more](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#checkbic)).

## [2.2.3] 2024-06-28

- **Select field for privacy page has been changed from InputfieldSelect to InputfieldPageAutocomplete**

  Due to an issue report by dotnetic in the support forum, there is a timeout problem on sites with a lot of pages if you are trying to open the configuration settings page on such large sites (Read more: https://processwire.com/talk/topic/26015-frontendforms-a-module-for-creating-and-validating-forms-on-the-frontend/?do=findComment&comment=242390).

  The fieldtype has now been changed from InputfieldSelect to InputfieldPageAutocomplete to prevent such a timeout issue.

- **Wrong filesize conversion fixed**

  This issue was also reported by dotnetic in the support forum. There was a conversion issue of the filesize set in the php.ini for the max file-size. In this file the you have to write the unit different than in all other cases. For example: 10 MB has to be written as 10M, 10 GB as 10G and so on. This led to mistakes during the conversion to Bytes, but should be fixed now.

- **Enter the filesize inside the allowedFileSize validator in different formats now**
  
  You are now able to enter the filesize inside the allowedfilesize validator not only in Bytes but also as a string with an unit (eg 10 MB). Read more [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#allowedfilesize).

- **New class to add simple markup to the form**

  Beside the append() and prepend() methods, which can be used to add some additional markup to any input field, you have now the possibility to add some extra markup to the form (independent of any input field). This can be done in the way like this:

  ```php
  $markup = new Markup();
  $markup->setMarkup('<p>This is my extra markup!</p>');
  $form->add($markup);
  ```

  Read more [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#additional-markup).

- **useInputWrapper(), useFieldWrapper() - 2 new methods for inputfields**

  These 2 new methods are the same as the useInputWrapper() and useFieldWrapper() methods that can be used with the form object. But in this case you can overwrite the settings of the form on per field base.

  This means, that if you have set to use the fieldwrapper on all input elements, you can disable for example the usage of the fieldwrapper on a specific field by using (in this case) the useInputWrapper(false) method. This gives you a little more flexibility.

  Read more [here](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#useinputwrapper---add-or-remove-the-inputwrapper-on-per-field-base)

## [2.2.4] 2024-07-06

- **getFormelementsByClass() - New method for form object**

  This method returns an array containing all form elements of a given class. For example you grab all Button objects or InputfieldEmail objects and so on.

  Read more [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#getformelementsbyclass---get-all-form-fields-of-a-specific-class)

- **getValues() method now returns all POST variables independent if they are added via Markup or as an object to the form**

  In previous versions of this module, the getValues() method had returned only the values of inputfields which has been added to the form object via the add() method and which were visible to the users (no hidden fields). Due to a user request, I have rewritten this method to also return hidden fields and inputfields which have been added for example via the Markup class and has not been added as an inputfield object to the the form object. Now you will get much more POST values with this method than before.

- **Support for your own custom CSS classnames file added**

  This idea comes from dotnetic in the support forum of the module. He uses an own CSS classnames file for his site and he has added this file inside the folder "CSSClasses". Now he can select his own file as the framework that should be used. It works fine, but the problem was that this folder will be overwritten during every update and so his file will be deleted everytime.

  To solve this problem, you can store your own CSS classes file now under "site/assets/files/FrontendForms/frameworks". This folder will not be overwritten during an update.

  You will find a more detailed explanation about this new possibility [here](https://github.com/juergenweb/FrontendForms/blob/main/README.md#support-for-css-frameworks).

- **Small JavaScript bug fixed**

  There was a little bug in the maxCharsCounterReverse() function: a missing check if a textarea is present. Thanks to dotnetic for reporting this issue and providing a fix too.

- **Size of CAPTCHA images reduced**

   The size of the CAPTCHA images has been reduced from 900px to 450px in length to prevent timeouts during the update. Over the time I have added so much different CAPTCHA images that the total size of all images is over 12MB now. This could lead to timeouts during an update of the module, especially if your connection is not very fast. To improve the download speed and to prevent such timeouts I have reduced all CAPTCHA images to the half size. 450px seems to be enough ;-)

## [2.2.5] 2024-07-11

- **Bug fixed for setting input field name**

  There was a bug that prevents the setting of a custom field name attribute for an inputfield. This is fixed now and you can set the name attribute different from the id attribute.

- **Bug fixed for throwing error message if minTime() will be rounded to 0**

  Thanks to Donatas from the forum, who reported a bug inside the checkTimeDiff() method, which calculates the time difference between loading the form and submitting the form.

  This method caluculates the minTime() depending on the mandatory fields left inside the form and rounded the value to seconds. If the value will be rounded to 0, an error occured. This is an rare edge-case-scenario, but it could happen.

  The bug fix sets the value to at least 1 second if the rounded value is lower than 1, so that it will never become 0. This prevents from stopping the code and firing an error message.

- **Inputfield dependencies added to show/hide/enable/disable form elements**

  With this new feature you can show or hide certain input fields (and other form elements) depending on the value(s) of other inputfields. In addition to show and hide you can enable or disable inputfields.

  You will find the complete description [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#inputfield-dependencies)

## [2.2.6] 2024-07-12

- **New configuration fields for setting global tags for label, description, notes, success and error message added**

Sometimes the tags set for an element don't fit your needs. For example, you want the description of the input field to be rendered within "div" tags and not with "p" tags. Until now, it was not possible to set the tags globally, but this gap is now closed with this update.

Many thanks to Donatas from the support forum, who made a request regarding changing the tags and thus gave me the idea to implement this feature.

  To make FrontendForms even more customizable, you can now change the tags for the following elements of an input field: Label, Description, Notes, Success and Error Message. In the backend you will now find 4 new configuration fields where you can change the tags globally.

  The global tags can be overwritten on per form base too with 4 new methods (setLabelTag(), setDescriptionTag(), setNotesTag() and setMessageTag()). 

  You will find more information about the methods [here](https://github.com/juergenweb/FrontendForms/blob/main/README.md#setlabeltag---change-the-tag-for-the-label-on-this-form)

  Last but not least you can overwrite the values set on per form base directly on the inputfield itself via the setTag() method (but this method is not new).

## [2.2.7] 2024-07-13

- **Small bug of inputfield dependencies and fieldset fixed**

If you are using a fieldset inside a form, you got an error, because the inputfield dependencies could not be used in combination with a fieldset. This has been fixed now.

## [2.2.8] 2024-07-13

- **Writing mistake in property name fixed**

Inside the password field there was a writing mistake for the property of the password length, which results in that the min length of the password will not be printed inside the password condition text (minlenght instead of minLength). This is fixed now

- **Add recognition for url segments in form action attribute**

The form action attribute, that has been automatically set during the instantiation of a form had only recognized the base url. If you are using url segments, and a form is submitted inside a segment, the form had always been redirected to the base url instead of the base url + segments. This has been fixed now and should work.

## [2.2.9] 2024-07-15

- **br tags from input radio multiple vertical removed**

This is a small update and contains only 1 styling bug fix: a br tag has been removed from inputfield radios multiple, if no framework has been selected. The br had created a wide gap between the radio buttons, which had looked very ugly.

## [2.2.10] 2024-07-17

- **Support for adding path to custom-json-classes file added**

This is an addition that comes from a user request by dotnetic. He needs to save his json-css-classes file on another directory than the default directory(site/assets/files/FrontendForms/frameworks). For this reason I have added a new
configuration field for entering the path to another directory. You will find this new configuration field under the framework select in the backend.

If you are not familiar with json-css-classes configuration files, please read the docs [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#support-for-css-frameworks).

## [2.2.11] 2024-07-20

- **Support for adding forms to CKEditor fields via placeholders**

This is an addition that goes back to a user request from donatas. He needs to load multiple forms into CKEditor fields. That's why he developed a working solution and offered me to integrate it into FrontendForms.

In short: By using placeholders in the style of {{myplaceholder}}, you can load forms into CKEditor fields and these placeholders will be replaced by forms during the page rendering process.

More details about how to use forms in CKEditor field are available inside the [docs](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#adding-forms-via-placeholders-to-ckeditor-fields).

- **A kind of namespace added to the main JS file of FrontendForms**

To avoid conflicts of variables and functions of FrontendForms with other javascript files, I added some kind of namespace to the main JS file. Now all functions and variables run in their own scope and there should be no conflict with other libraries or JS files.

## [2.2.12] 2024-07-21

Version 2.2.12 contains 1 bug fix, 1 improvement and 2 HTML5 validators.

- **Bug fixed on validation of checkbox if it is required**

By default, checkboxes can have a value, but it is not mandatory. There was a bug validating a checkbox if it has no value. In this case the required validator has been ignored. An additional condition was added to check if an inputfield is type of checkbox. If so, then the validation rules added will run, even if the checkox has no value. Now the checkbox will be validated if it is checked even if there is no value set.

- **Calculation of new min time does not run on first form submission**

If minTime() and/or maxTime() are set, the checkTimeDiff() method always calculates a new min time value after each form submission depending on the empty mandatory fields left. It makes no sense if the calculation starts at the first form submission, so I have changed this to start the calculation at the second submission.

- **HTML5 validation for IBAN and BIC added**

The missing HTML5 validation for IBAN and BIC validation rules have been added now. 

## [2.2.13] 2024-08-10

Version 2.2.12 contains 1 upgrade for the FrontendFormsManager and one bug fix

- **Upgrade for the FrontendFormsManager**

The FrontendFormsManager now contains a new section that contains the number of temporary blocks of all IP addresses.
Temporary means that these IPs are blocked over a session. As long as the session exists, these IPs will not be able to submit a form.

You can also find the same section in the module configuration, but it is more user-friendly to work with blocks in the FrontendFormsManager than in the module configuration.

Additionally, you also have a graph that shows the temporary blocking of each IP.

The purpose of this section is to identify potential spammers and add their IPs to the blacklist. Any IP on the blacklist will no longer be able to submit a form on the website because the forms for those IPs will no longer be displayed.

So this is a sensible measure against spammers.

- **Bug fixed for null values in validation rule "listContains"**

I made 2 small changes in the Validator.php. The "listContains" validation rule now handles null values correctly.

## [2.2.14] 2024-08-27

- **Bug for wrong url to privacy page fixed**

If you are using the text version of the privacy text, the link to the privacy page was wrong and you will be always redirected to the homepage instead. This bug is fixed now.

- **New Captcha added**

A new slider captcha has been added. This captcha must be solved by placing a puzzle piece in the correct position. This captcha is of the same type as the [Slide Captcha module](https://processwire.com/modules/captcha-slide/), but it is not the same and runs with its own code. This is why you don't need to install this captcha module either. This captcha is fully integrated with FrontendForms and all you have to do is select the slider captcha in the module configuration.

- **Bug for label tag on checkbox/radio multiple fixed**

The label tag of checkbox and radio multiple had been rendered as a div instead of a label tag. This is fixed now. Thanks to donatasben for reporting this issue. 

## [2.2.15] 2024-08-30

- **Bug fixed inside in the Form.php caused by using the hasAttribute() method in combination with elements of the Markup class**

Thanks to Donatas for reporting a bug in the Form.php where form elements of the "Markup" class are used within the form, and using the hasAttribute() method causes an error message on line [3071](https://github.com/juergenweb/FrontendForms/blob/main/Formelements/Form.php#L3071).

- **New configuration field for embedding JS and CCS files added**

A new configuration field in the backend allows you to select on which pages the JS and CSS files should be embedded. This increases the loading performance of pages, because the the files can only be added to pages that contain a form and not on all pages.

## [2.2.16] 2024-10-08

- **Remove hover command on tables at "template_1" email template**

There was as CSS hover effect added to td elements on tables inside the template_1 email template. This makes no sense and has been removed.


- **New method to stop random rotation of Honeypot field added**

A user reported an issue at a complex grid form, where the rotation of the Honeypot field breaks the layout. This should not be happen, but now you can disable the Honeypot field rotation to position the Honeypot field fixed on the very first position.

[Read more](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#stophoneypotrotation---stop-the-randomly-rotation-of-the-honeypot-field-on-per-form-base)

## [2.2.17] 2024-10-19

- **Class names of the slider CAPTCHA have been changed to prevent naming conflicts**

According to an [issue report by DrewPH](https://processwire.com/talk/topic/26015-frontendforms-a-module-for-creating-and-validating-forms-on-the-frontend/page/12/#comment-245163) in the forum, I have changed all class names of the slider CATPTCHA by adding the prefix "ffm", which stands for "FrontendForms module".

The problem was that the class name "block" of one element of the slider CAPTCHA results in a conflict with CSS of other elements on a site. This class name was indeed not really unique ;-). To prevent further conflicts in the future I have decided to re-name all other classes of the CAPTCHA elements too to make them more unique.

*Please note:* If you have overwritten the CSS for these classes by your own (fe to change a color or something like that), you have to take care about the new class names. This means that you have to adapt your overwritten CSS to the new class names.
 

Files that have been changed:

- frontendforms.css
- frontendforms-slidercaptcha.js

## [2.2.18] 2024-10-26

- **JS Bug fixed if textarea counter is not present at a textarea field**

According to an issue report [here](https://processwire.com/talk/topic/26015-frontendforms-a-module-for-creating-and-validating-forms-on-the-frontend/page/12/#comment-245295) in the forum, the bug has been fixed now.

- **JS Bug fixed for calling mfConditionalFields() on forms with no ID attribute**

To run the field dependencies function mfConditionalFields() on a form, it is mandatory that the form has an ID attribute. If not an error occurs inside the console. This bug has been fixed now.

- **JS Bug fixed for calling Slider CAPTCHA after AJAX call again**

The Slider CAPTCHA has not been loaded again if form has been submitted via AJAX, but there were errors. This bug has been fixed now and the CAPTCHA loads properly. 

- **New configuration field for AJAX submission added**

According to a user request by DrewPH, a new configuration field to customize the text of the info message next to the progress bar during an AJAX request has been added. By default the message "Please be patient... the form will be validated!" will be displayed. This text can be changed now inside the module configuration inside the AJAX tab.

- **New method setAjaxMessage() added**

This method does the same as the previous mentioned new configuration field, but you can change the global info text on per form base. This means that you can overwrite the default or global value of the info text for the AJAX request directly on the form. So each form can have a different text that will be displayed during the AJAX request.

Read more about this new method [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#setajaxmessage---customize-the-info-text-during-an-ajax-submission-on-per-form-base)

## 2024-10-27

- **Support for RockLanguage added**

If you have installed the [RockLanguage](https://processwire.com/modules/rock-language/) module by Bernhard Baumrock, this module now supports the sync of the language files. This means that you do not have to take care about new translations after you have downloaded a new version of FrontendForms. All new translations (at the moment only German translations) will be synced with your your ProcessWire language files. 

Please note: The sync will only take place if you are logged in as Superuser and $config->debug is set to true (take a look at the [docs](https://www.baumrock.com/en/processwire/modules/rocklanguage/docs/)).

The (old) CSV files usage is still supported.

## [2.2.19] 2024-11-09

- **Bug fixed: class "fieldwrapper" has not been added to outer container if inputfield dependencies are used**

  If inputfield dependencies are used, the most outer container must contain the class "fieldwrapper" to show or hide certain form elements. This was not the case and has been fixed now.
  
- **Bug fixed: validation rules on file inputfields have been removed accidentaly**

  The problem was, that all validation rules on file upload fields have been removed due to a wrong if-condition. This has been fixed now.
  
- **New method getUploadPath() added**

  This method is primarly for internal usage and outputs the path, were uploaded files will be stored.

## [2.2.20] 2024-11-14

- **Namespace problem by usage with Phalcon PHP-framework fixed**

  According to this [issue](https://github.com/juergenweb/FrontendForms/issues/19), I removed the usage of the wireClassNamespace() function by using a hard-coded namespace instead.

- **Classloader path fixed**

Thanks to Donatas for providing a [pull request](https://github.com/juergenweb/FrontendForms/pull/20) to fix a path problem inside the Tag.php.  

## [2.2.21] 2024-12-09

- **Wrong logo path fixed**

At the email template *template_3.html* there was a wrong logo path which prevents the logo to be displayed inside the email. This has been fixed now.

- **New userfriendly HTML email template selector added**

The default select input element for selecting a email template globally has been replaced by a stylish image picker, which displays thumbnails of the email templates. Now you can see, what the template looks like instead of seeing only the name of the template file.
You can read more about it [here](https://github.com/juergenweb/FrontendForms?tab=readme-ov-file#email-templates)

## [2.2.22] 2024-12-11

- **Bug related to JQuery image-picker script fixed**

The initialization of the image-picker script resulted in manipulation of all select inputs on the site. The cause of this was, that script affected all select inputs instead of a specific one for the template selector. This has been fixed now. Thanks to Chris-PW from the forum for reporting this issue.

## [2.2.23] 2024-12-13

The new features come from a user request by donatasben. Thanks for your suggestions!

- **Performance update: JavaScript loading on certain conditions added**

By default, there were only 2 conditions that could be set in the backend: load all JavaScript files in the frontend or not.

This module contains 3 different javascript files that are added to the frontend: The main JS file, the JS file for field conditions, and the JS file for the slider captcha.

If embedding the JS files is selected, then the main JS and the JS field condition will be loaded, regardless of whether you use field conditions or not.

The first performance update now includes a check for whether or not at least 1 field condition is used on a field on the page. If none of the fields contain a field condition, the corresponding JS field condition file is not added to the page.

The second performance update includes a check if the slider captcha has been selected in the backend and that the slider captcha is not disabled on per form base on a page. So if you have enabled the slider captcha globally, but have disabled it in all forms on a given page, then the JS file for the slider captcha will not be added to the frontend (regardless of the backend settings).

- **API update: Enable/disable JS/CSS files on per form base**

Until now, you only had the option to enable/disable the embedding of JS/CSS in the frontend in the backend configuration.

Now you can enable/disable this on per form base too by using 2 new methods: [useCSS()](https://github.com/juergenweb/FrontendForms/blob/main/README.md#usecss---enabledisable-the-embedding-of-css-files-on-per-form-base) and [useJS()](https://github.com/juergenweb/FrontendForms/blob/main/README.md#usejs---enabledisable-the-embedding-of-js-files-on-per-form-base).

This new functionality takes care of looking at all forms on a page. If at least 1 form has JS enabled, then the JS file will be embedden, otherwise not. This works on the CSS files too.

## [2.2.24] 2024-12-15

This is a performance update only.

- **Loading of minified versions of the JavaScript and CSS files added**

I've added a minified version of all 3 module JavaScript files and the CSS file to the module to save a bit of headroom and boost performance. Now only the minimized versions are added to the frontend. The standard versions are still available, but are only used for further development.

So, if you want to take a look at these files, it is better to look at the non-minified versions.

- **Pagelist select for adding JS and CSS removed and automatic form detection on page added**

In the module configuration, you had the option to select the pages where the JS and CSS files should be inserted, in order to load only the files on pages where there is at least 1 form. This increased performance by loading these scripts only on pages with forms and not on all pages.

This is no longer necessary, as an automatic check has been added to check whether forms are present on a page or not. If so, then the files will be inserted into the page if the embedding is not prevented by another setting.

With this new feature, you don't have to worry about whether or not to load the files on a page.

## [2.2.25] 2024-12-16

This updated fixes some bugs.

- **Adding higher priority to addAssets() hook**

The priority has been increased from 100 to 999 because this hook method is supposed to run later. There was a conflict with another Page::render hook and this is now resolved with the new priority.

- **Using form placeholders in multilanguage Textareas fixed**

Multilingual text areas were not taken into account when you use placeholders for forms in them. This is now fixed.


- **Javascript error in frontendforms.js fixed**

Due to the selective loading of the mf-conditional-fields.js, an error occurred when this JS file was not loaded. This bug has now been fixed by first checking if this file exists before executing the mfConditionalFields function.

## [2.2.26] 2024-12-18

This updated contains a some new code for other modules base on FrontendForms.

- **New method for creating email template options added**

A new method, which could be used in other modules based on Frontenforms has been added. This new method creates the new email template selector switch with images of each email template, as used inside the module configuration of this module.
With this new method, this new nice image picker select could be used in other modules too (and will be added to this modules in the future).

## [2.2.27] 2024-12-26

- **New language files added**

Russion language files added to the RockLanguage folder (Thanks to andy-scboy).

- **Bug in Markup.php fixed**
  
[Issue #24 fixed - error for non-existing method containsConditions() inside Markup class](https://github.com/juergenweb/FrontendForms/issues/24)

The absence of the containsConditions() method inside the Markup class leads to an error, so this method has been added to this class and the containsConditions() method has been rewritten to output always false if form object is not of type inputfield.

- **Wrong privacy field position fixed**

Thanks to Frank Vèssia who posted a [problem with the wrong position of the privacy accept field](https://processwire.com/talk/topic/28352-frontendloginregister-a-configurable-module-for-fast-implementation-of-an-user-management-system-based-on-frontendforms/?do=findComment&comment=246594) inside the form if no CAPTCHA was used. The problem was the this field appears on the top instead of the bottom if no CAPTCHA was used - this issue has been fixed now.

- **New configuration fields for adding external URL for a privacy policy page added**

This feature comes from a user request (Frank Vèssia), where a link to an external privacy page was needed. In the past, you had only the possibility to select an internal PW page as your privacy policy page. Now you have also the possibility to select an external URL as link to your privacy page. 

## [2.2.28] 2025-02-01

- **Hooks for creating custom output fixed**

Thanks to PWaddict from the forum, who reported the issue, that the hooks described inside the [Hooking section](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#hooking) does not work at all. 

After a long time of searching and via the help of RobinS and Bernhard from the support forum, the problem could be solved.

The issue was caused by using the 3 underscores at function calls which was responsible to bypass the hook. I have corrected this on every file inside the module and it should work now without problems. 

In addition I have added 2 more hook examples at the Hooking section in the docs, where you can add FontAwesome icons in front of the field description and field notes. 

## [2.2.29] 2025-02-18

- **Validation rule for cyrillic name added**

Thanks to Andy from the support forum, who wrote this validation rule and shared it with us. Now you can easily validate cyrillic names. You will find an example here in the docs.

- **New section for adding custom validation rules added to the docs**

You have the possibility to create and add your own custom validation rules to a form. The new [doc section](https://github.com/juergenweb/FrontendForms/tree/main?tab=readme-ov-file#create-your-own-custom-validation-rules) explains exactly how to do this.

- **Wrong path to custom CSS-frameworks fixed**

Thanks to [dotnetic](https://processwire.com/talk/profile/1299-dotnetic/) from the forum who reported this [issue](https://processwire.com/talk/topic/26015-frontendforms-a-module-for-creating-and-validating-forms-on-the-frontend/page/14/#comment-247468). It has been fixed now.

## [2.2.30] 2025-03-25

- **New method removeOptionByValue() added**

This new method can be used to remove a specific option element by its value from an element with options. This new method can be used by the following classes:

* InputRadioMultiple

- **New code segments for upcoming module FieldtypeFrontendComments added**

I have planned to release a new module to output a comment section on a page. For this reason some new code lines have been added. They have no effect for the FrontendForms module itself.

- **Position change issue on checkboxes and radio buttons fixed**

According to [this issue](https://processwire.com/talk/topic/26015-frontendforms-a-module-for-creating-and-validating-forms-on-the-frontend/?do=findComment&comment=248044) reported by [PWaddict](https://processwire.com/talk/profile/3919-pwaddict/) in the forum, there was a problem by changing the position of the label tag on checkboxes and radios. The backend setting configuration were not taken into account. This issue is fixed now.

- **Inputfield rendering for different CSS frameworks fixed**

There was a bug in the function that checks for rendering of the form field markup according to different CSS frameworks. The check has always returned false, because of the missing 3 underscores of the functions name inside the check. So the markup has always been rendered with the default rendering function instead of using rendering functions for Bootstrap, Pico and so on. This has been fixed now. 

- **New ResetButton class added**

A new predefined standard form element type has been added to create a reset button. The new class can be found here: [https://github.com/juergenweb/FrontendForms/blob/main/Formelements/Miscellaneous/defaults/ResetButton.php](https://github.com/juergenweb/FrontendForms/blob/main/Formelements/Miscellaneous/defaults/ResetButton.php).

This button element can be added to the form to clear the entered form data. It is added in the same way as the Submit button.
