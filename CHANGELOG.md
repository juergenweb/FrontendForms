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

## [2.1.26] 2023-03-27
Missing translations for image CAPTCHA added.
