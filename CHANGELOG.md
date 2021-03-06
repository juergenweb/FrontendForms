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
  2 new methods for the WireMail class were added to use HTML email templates

## [2.0.1] - 2021-12-20

### Corrections
- various corrections of bugs and translations

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
Adding support for boolean parameter in getValues() method, wheter to output only input field values or values from
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
By default, a small CSS-file to hide the honeypotfield will be added. You can disable the embedding of this file in the
backend.
#### Set max attempts for form submission
Set max number of failed attempts globally until a visitor will be blocked.
#### Set min before a form should be submitted
Set min time before the submit button should be pressed to prevent spambots filling out the form.
#### Set max until a form should be submitted
Set max time until the submit button should be pressed to prevent spambots analyzing the form.
#### Enable/disable logging of failed attempts
Possibility to save every blocked IP address including name of the form and date/time as timestamp to the log files.
#### Create list of black listed IP addresses
Enter IP addresses that should be prevented from visiting the forms (fe IP addresses from spammers,...)
#### Set global custom texts for required fields, success and error message 
Now you can enter your custom messages inside the backend configuration.

## [2.1.3] - 2022-07-13
### Adding file upload for sending emails with attachements
New class InputFile added. It allows to upload files (single or multiple) that can be sent via email.
The depreceated method addEmailTemplate was replaced be the new method mailTemplate(). This method can
overwrite the choosen template from the global configuration if needed
To send emails with attachements you need to use the new method sendAttachments() instead of the WireMail
method attachement().
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
