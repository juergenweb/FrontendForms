<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class containing all custom validators
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: CustomRules.php
 * Created: 25.02.2023
 * Optimized via Claude AI 06.05.26
 */

use DateInterval;
use DateTime;
use Exception;
use ProcessWire\User;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;
use Valitron\Validator as V;

class CustomRules extends Tag
{

    /**
     * Cache for prepared ZIP validation data
     * Prevents multiple expensive filesystem operations
     *
     * @var array
     */
    private array $zipValidationCache = [];

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();

        // set path to the language folder
        V::langDir($this->wire('config')->paths->siteModules . 'FrontendForms/valitron/');
        // set language for the error messages to use the errormessage.php inside valitron folder
        V::lang('errormessages');

        // set often used variable to speed up the validation by calling them only once

        $passwordModule = $this->wire('modules')->get('InputfieldPassword');
        $passwordField = $this->wire('fields')->get('pass');
        $requirements = $passwordField->requirements ?: $passwordModule->requirements;
        $passwordModule->set('requirements', $requirements);

        $wire = $this->wire();
        $user = $this->wire('user');
        $sanitizer = $this->wire('sanitizer');
        $users = $this->wire('users');
        $input = $this->wire('input');
        $config = $this->wire('config');
        $files = $this->wire('files');
        $pages = $this->wire('pages');
        $page = $this->wire('page');
        $session = $this->wire('session');

        /**
         * 1) Check if username is unique
         */
        V::addRule('uniqueUsername', function ($field, $value) use ($user, $sanitizer, $users) {
            // Fast path: same user keeps their username
            if ($user->isLoggedin() && $user->name === $value) {
                return true;
            }

            $value = $sanitizer->pageName($value);

            // Use users API directly (faster + correct intent)
            return !$users->get("name=$value")->id;
        }, $this->_('must be unique. This username is already in use. Please choose another username.'));

        /**
         * 2) Check if username and password match
         */
        V::addRule('matchUsername', function ($field, $value, array $params) use ($input, $sanitizer, $users) {
            $fieldName = $this->getFieldName($params[0]); // get field name including form id prefix
            $username = $input->$fieldName;
            $username = $sanitizer->pageName($username); // important
            $u = $users->get($username);
            if ($u->id && $u->pass->matches($value)) {
                return true;
            }
            // set username and password entered inside a session that can be used later if needed
            // can be reached via $this->wire('session')->get('name of the username field');
            // returns a multidimensional array containing the entered username => password combinations
            // can be used later on to check if multiple attempts with same username and different password
            // combinations were taken, to fe lock the user account due to security reasons.
            $this->createSessionForLoginAttempts($fieldName, 'username', $value);
            return false;
        }, $this->_('and username do not match.'));


        /**
         * 3) Check if password meets the conditions set in the backend
         * fe. 8 characters long, must contain number, must contain upper and lowercase...
         * Add field name as first param if field name is not pass
         */
        V::addRule('meetsPasswordConditions', function ($field, $value) use ($passwordModule) {
            return $passwordModule->isValidPassword($value);
        }, $this->_('does not meet the conditions.'));

        /**
         * 4) Username may contain lowercase a-z, 0-9, hyphen or underscore
         */
        V::addRule('usernameSyntax', function ($field, $value) {
            return preg_match('/^[a-z0-9_-]+$/', $value) === 1;
        }, $this->_('contains not allowed characters. User name may contain lowercase a-z, 0-9, hyphen or underscore (no whitespaces).'));

        /**
         * 5) Check if email is unique
         */
        V::addRule('uniqueEmail', function ($field, $value) use ($wire, $users, $sanitizer) {
            // Fast path: same user keeps their email
            $currentUser = $wire->user;
            if ($currentUser->isLoggedin() && $currentUser->email === $value) {
                return true;
            }

            // Check if email already exists
            $value = $sanitizer->selectorValue($value);
            return !$users->get("email=$value")->id;
        }, $this->_('must be unique. This email is already in use. Please use another email address.'));

        /**
         * 6) Check if entered password is the correct password stored in the DB for the current user
         * This is useful fe if the user has to fill in the old password before he enters the new one
         * As param enter the user object
         * $field->setRule('checkPasswordOfUser', $user);
         */
        V::addRule('checkPasswordOfUser', function ($field, $value, array $params) {
            return ($params[0] instanceof User) && $params[0]->pass->matches($value);
        }, $this->_('entered is not the same password as the one, that is stored in the database.'));

        /**
         * 7) Check if email and password match
         */
        V::addRule('matchEmail', function ($field, $value, array $params) use ($users, $sanitizer, $input) {
            $fieldName = $this->getFieldName($params[0]); // get field name including form id prefix
            $email = $input->$fieldName;
            $email = $sanitizer->selectorValue($email);
            $u = $users->get("email=$email");
            if (($u->id != 0) && ($u->pass->matches($value))) {
                return true;
            }
            // set email and password entered inside a session to can be used later if needed
            // can be reached via $this->wire('session')->get('name of the email or username field');
            // returns a multidimensional array containing the entered username/email => password combination
            // can be used later on to check if multiple attempts with same username/email and different password
            // combinations were taken, to fe lock the user account due to security.

            $this->createSessionForLoginAttempts($fieldName, 'email', $value);
            return false;
        }, $this->_('and email do not match.'));


        /**
         * 8) Check if value is boolean true
         */
        V::addRule('isBooleanAndTrue', function ($field, $value) {
            return $value === true;
        }, $this->_('is not Boolean true.'));


        /**
         * 9) Check if value is boolean false
         */
        V::addRule('isBooleanAndFalse', function ($field, $value) {
            return $value === false;
        }, $this->_('is not Boolean false.'));


        /**
         * 10) Check if the value is exact the same value as entered as param
         */
        V::addRule('exactValue', function ($field, $value, array $params) {
            return ($value === $params[0]);
        }, $this->_('does not have the expected value.'));


        /**
         * 11) Special ProcessWire method to check if the code of Tfa is correct
         * Supports following modules at the moment: TfaEmail, TfaTotop
         * It uses the public method isValidUserCode() from the module class for validation, so
         * it should work with all Tfa modules
         * @param: Enter following values as params:
         * [0]: User object: The user object of the user who tries to log in with Tfa enabled
         * [1]: Module: The Tfa module object which is used by the user
         * @return boolean
         */
        V::addRule('checkTfaCode', function ($field, $value, array $params) {
            $user = $params[0]; // user object
            $module = $params[1]; // module object
            $className = $module->className(true);
            // start instance
            $tfa = new $className();
            return $tfa->isValidUserCode($user, $value, $module->getUserSettings($user));
        }, $this->_('is not correct.'));


        /**
         * 12) Check if the value entered is different from another value entered as param
         * $field->setRule('differentValue', 'test'); // entered value must be different from "test" to return true
         */
        V::addRule('differentValue', function ($field, $value, array $params) {
            return ($value !== $params[0]);
        }, $this->_('entered must be different than the value specified.'));


        /**
         * 13) Check if the new password entered is different the old one
         * $field->setRule('differentPassword', $user);
         */
        V::addRule('differentPassword', function ($field, $value, array $params) {
            if ($params[0]->pass->matches($value)) {
                return false;
            }
            return true;
        }, $this->_('entered must be different from the old password.'));


        /**
         * 14) Check if captcha value is correct
         * This is a special validator for the integrated captcha field
         */
        V::addRule('checkCaptcha', function ($field, $value, array $params) {
            return ($value === $params[0]);
        }, $this->_('value entered is not correct.'));


        /**
         * 15) Check if password is safe - in other words: is not in the list of the most popular passwords
         */
        V::addRule('safePassword', function ($field, $value) use ($config, $files) {
            $passwordPath = $config->paths->siteModules . 'FrontendForms/passwords.txt';
            return !$files->exists($passwordPath) || !count($this->findWords($value, $passwordPath));
        }, $this->_('value is in the list of the most popular passwords and is therefore not secure. Please choose another one.'));

        /**
         * 16) Check if the uploaded file is not larger than the allowed filesize
         */
        V::addRule('allowedFileSize', function ($field, $value, array $params) {

            if (empty($params[0]) || empty($value)) {
                return true;
            }

            $maxSize = Inputfields::convertToBytes($params[0]);

            foreach ($value as $file) {
                if ($file['size'] > $maxSize) {
                    return false;
                }
            }

            return true;
        }, $this->_('contains a file that is larger than the allowed filesize.'));

        /**
         * 17) Check if an error occurred during the upload of the file
         */
        V::addRule('noErrorOnUpload', function ($field, $value) {
            return !array_filter($value, fn($f) => $f['error'] !== 0 && $f['error'] !== 4);
        }, $this->_('caused an error during the upload.'));


        /**
         * 18) Check if uploaded files are of the allowed extensions
         */
        V::addRule('allowedFileExt', function ($field, $value, array $params) {
            $allowed = array_flip($params[0]);
            foreach ($value as $file) {
                if ($file['error'] === 0 && !isset($allowed[strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))])) {
                    return false;
                }
            }
            return true;
        }, $this->_('does not belong to the allowed file types: %s.'));


        /**
         * 19) Check if the uploaded file is not larger than the allowed filesize as declared inside the php.ini
         */
        V::addRule('phpIniFilesize', function ($field, $value) {

            static $ini = null;
            $ini ??= Inputfields::convertToBytes(ini_get('upload_max_filesize'), true);
            foreach ($value as $file) {
                if ($file['error'] === 0 && $file['size'] > $ini) {
                    return false;
                }
            }
            return true;

        }, $this->_('is larger than the max. allowed filesize.'));

        /**
         * 20) Check if uploaded files are not of the forbidden extensions
         * This is the opposition of the allowedFileExt
         */
        V::addRule('forbiddenFileExt', function ($field, $value, array $params) {
            $forbidden = array_flip($params[0]);
            foreach ($value as $file) {
                if ($file['error'] === 0) {
                    if (isset($forbidden[strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))])) {
                        return false;
                    }
                }
            }
            return true;
        }, $this->_('is of one of the forbidden file types: %s.'));

        /**
         * 21) Check if entered string is a valid time
         */
        V::addRule('time', function ($field, $value) {
            return !$value || preg_match('#^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$#', $value);
        }, $this->_('is not a valid time. You have to enter the time in this format: HH:MM:SS (fe. 19:00:00)'));


        /**
         * 22) Check if entered string is a valid month string
         * Format: YYYY-MM fe 2023-03
         */
        V::addRule('month', function ($field, $value) {
            return !$value || preg_match('#^\d{4}-(0[1-9]|1[012])$#', $value);
        }, $this->_('is not a valid month. You have to enter the month in this format including the year: YYYY-MM (fe. 2023-06)'));


        /**
         * 23) Check if entered string is a valid week string
         * Format: YYYY-Www fe 2023-W25
         */
        V::addRule('week', function ($field, $value) {
            return !$value || preg_match('#^\d{4}-W((?:0[1-9]|[1-4]\d|5[0-3]))$#', $value);
        }, $this->_('is not a valid week. You have to enter the week in this format including the year: YYYY-Www (fe. 2023-W06)'));

        /**
         * 24) Check if entered string is a valid hexadecimal color code
         * Format: #666 or #666666
         */
        V::addRule('checkHex', function ($field, $value) {
            return !$value || preg_match('/^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $value);
        }, $this->_('is not a valid hexadecimal color code.'));

        /**
         * 25) Check if a date is before a date which is entered in another field
         */
        V::addRule('dateBeforeField', function ($field, $value, array $params) {
            $date_1 = $this->getValue($params[0]);
            return !$date_1 || $this->compareDates($date_1, $value);
        }, $this->_('must be before %s.'));

        /**
         * 26) Check if a date is after a date entered inside another field
         */
        V::addRule('dateAfterField', function ($field, $value, array $params) {
            $date_1 = $this->getValue($params[0]);
            return !$date_1 || $this->compareDates($date_1, $value, false);
        }, $this->_('must be after %s.'));

        /**
         * 27) Check if a date is within a given time-range in days depending on another field
         */
        V::addRule('dateWithinDaysRange', function ($field, $value, array $params) {
            return $this->checkDateRange($params[0], $value, $params[1]);
        }, $this->_('must be within the allowed time range.'));

        /**
         * 28) Check if a date is outside a given time-range in days depending on another field
         */
        V::addRule('dateOutsideOfDaysRange', function ($field, $value, array $params) {
            return $this->checkDateRange($params[0], $value, $params[1], false);
        }, $this->_('must be outside the forbidden time range.'));

        /**
         * 29) Check international first and last name
         * Based on https://regexpattern.com/international-first-last-names/
         */
        V::addRule('firstAndLastname', function ($field, $value) {
            static $pattern = '/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.\'-]+$/u';
            return preg_match($pattern, $value);
        }, $this->_('contains invalid characters for a name.'));


        /**
         * 30) Check if the filename of an uploaded file is unique inside the upload directory
         */
        V::addRule('uniqueFilenameInDir', function ($field, $value, $param) {
            foreach ($value as $file) {
                if (!$this->checkDuplicateFilename($file, $this->uploadPath, $param)) {
                    return false;
                }
            }
            return true;
        }, $this->_('contains a file that has the same file name as a file stored in the destination directory.'));

        /**
         * 31) Check if text is inside an array of various texts
         * This validator was especially designed for the question CAPTCHA
         */
        V::addRule('compareTexts', function ($field, $value, $params) {
            if (!is_array($params[0])) {
                throw new Exception($this->_('Please add only an array as second parameter.'));
            }
            if (empty($params[0])) {
                return true;
            }
            $valueLower = strtolower($value);
            foreach ($params[0] as $answer) {
                if (strtolower($answer) === $valueLower) {
                    return true;
                }
            }
            return false;
        }, $this->_('contains not the correct answer.'));

        /**
         * 32) Check if international IBAN is in the right format/syntax
         */
        V::addRule('checkIban', function ($field, $value) {
            return $this->validateIBAN($value);
        }, $this->_('is not in the correct format.'));

        /**
         * 33) Check if international BIC code is in the right format
         */
        V::addRule('checkBic', function ($field, $value) {
            return $this->validateBIC($value);
        }, $this->_('is not in the correct format.'));

        /**
         * 34) Check if the x and y positions of the slider CAPTCHA are correct
         * This validator is only for internal usage on the slider CAPTCHA to provide
         * server-side validation too.
         */
        V::addRule('checkSliderCaptcha', function ($field, $value, $params) {

            $xPos = $params[0] ?? false;
            $yPos = $params[1] ?? false;
            $id = $params[2] ?? false;

            $sessionXPos = $this->wire('session')->get($id . '-captcha_x') ?? false;
            $sessionYPos = $this->wire('session')->get($id . '-captcha_y') ?? false;

            $xError = abs($sessionXPos - $xPos);
            $yError = abs($sessionYPos - $yPos);

            return ($xPos !== false && $yPos !== false && $sessionXPos !== false && $sessionYPos !== false && $xError < 0.0001 && $yError < 0.0001);

        }, $this->_('has not been solved correctly.'));

        /**
         * 35) Check if the value entered is a correct cyrillic name
         */
        V::addRule('cyrillicName', function ($field, $value) {
            return !preg_match('/[^а-яё\-]/iu', $value);
        }, $this->_('contains not allowed characters. Cyrillic name may contain lowercase and uppercase а-я and hyphen (no whitespaces).'));

        /**
         * 36) Check if file upload field is not empty (required validation for upload field)
         * This validator is necessary because the default validator for "required" does not work on upload fields
         * This validator is more intended to be an internal validator that will be added automatically if a file upload field is required
         * So you do not have to take care to add it manually to a file upload field
         * BTW there are no negative side effects if you add this validator manually to a file upload field
         */
        V::addRule('fileRequired', function ($field, $value) {
            return is_array($value) && isset($value[0]['size']) && $value[0]['size'] !== 0;
        }, $this->_('is required.'));

        /**
         * 37) Check number of files inside file input multiple
         */
        V::addRule('allowedFileNumber', function ($field, $value, array $params) {
            return !isset($params[0]) || intval($params[0]) >= count($value);
        }, $this->_('contains more files than the allowed number of files.'));

        /**
         * 38) Check total file size inside file input multiple
         */
        V::addRule('allowedTotalFileSize', function ($field, $value, array $params) {
            return !isset($params[0]) || array_sum(array_column($value, 'size')) <= Inputfields::convertToBytes($params[0]);
        }, $this->_('contains files whose total size is larger than the total allowed size.'));

        /**
         * 39) Check if a string does not contain a letter (including German Umlauts)
         */
        V::addRule('noLetters', function ($field, $value) {
            return !preg_match('/[a-zA-ZäöüÖÄÜ]/u', $value);
        }, $this->_('contains letters which are not allowed.'));

        /**
         * 40) Check if a string does not contain a number
         */
        V::addRule('noNumbers', function ($field, $value) {
            return !preg_match('/\d/', $value);
        }, $this->_('contains at least one number, but this is not allowed.'));

        /**
         * 41) Check if the field has a value if another field contains a specific value
         * Can check for a single value or for an array with and|or operator
         * $params[0]: the name of the conditional field
         * $params[1]: the value(s) to compare: must be a string separated by |
         * $params[2]: the operator -> either "and" or "or"
         */
        V::addRule('requiredIfEqual', function ($field, $value, $params) {

            // Early exit if field doesn't exist
            if (!$this->getFormElementByName($params[0])) {
                return true;
            }

            $conditionalFieldValue = $this->getValue($params[0]);
            $equalValues = $params[1];

            // Parse equal values into array
            if (is_string($equalValues) && str_contains($equalValues, '|')) {
                $equalValues = explode('|', $equalValues);
            }

            // Determine operator
            $operator = ($params[2] ?? false) === true ? 'and' : 'or';

            // Check if condition is met
            if (is_array($equalValues)) {
                if ($operator === 'and') {
                    // AND operator: exact match
                    $conditionMet =
                        is_array($conditionalFieldValue)
                        && count(array_intersect($conditionalFieldValue, $equalValues)) === count($equalValues);
                } else {
                    // OR operator: intersection check
                    $conditionMet = is_array($conditionalFieldValue) && count(array_intersect($conditionalFieldValue, $equalValues)) > 0;
                }
            } else {
                // Single value comparison
                $conditionMet = $conditionalFieldValue == $equalValues;
            }

            // If condition is met, value is required
            return !$conditionMet || (bool)$value;
        }, $this->_('is required.'));

        /**
         * 42) Check if the field has a value if another field contains a value (not a specific value - any value)
         */
        V::addRule('requiredIfNotEmpty', function ($field, $value, $params) {
            return
                !$this->getFormElementByName($params[0])
                || empty($this->getValue($params[0]))
                || !empty($value);
        }, $this->_('is required.'));

        /**
         * 43) Check if the field has a value if another field contains no value
         */
        V::addRule('requiredIfEmpty', function ($field, $value, $params) {
            return
                !$this->getFormElementByName($params[0])
                || !empty($this->getValue($params[0]))
                || !empty($value);
        }, $this->_('is required.'));

        /**
         * 44) Check if a value is unique inside a specific PW field
         * $params: first param is the field name, second param are specific templates that contain the PW field
         * The first parameter (PW field) ist required
         * The second parameter (template) is mandatory => string (single template name) or array (multiple template names)
         * Please note: The check of the value ist NOT CASE SENSITIVE because selectors do not support case sensitivity
         */
        V::addRule('uniqueStringValueOfPWField', function ($field, $value, $params) use ($sanitizer, $pages) {
            $fieldName = $params[0];
            $value = $sanitizer->string($value);
            $value = $sanitizer->selectorValue($value);

            $selector = count($params) > 1
                ? "template=" . (is_string($params[1]) ? $params[1] : implode('|', $params[1])) . ",$fieldName=$value, limit=1"
                : "$fieldName=$value, include=all, limit=1";
            return !$pages->find($selector)->count();
        }, $this->_('is already in use. Please enter a different value.'));

        /**
         * 45) Validates the min number of files inside a ZIP folder
         * Set the min number as int or string as $param
         */
        V::addRule('minFilesInZIPFolder', function ($field, $value, $params) {
            return $this->checkZipFilesNumber($params[0], $field);
        }, $this->_('contains less files inside a ZIP file than the required number of %s files.'));

        /**
         * 46) Validates the max number of files inside a ZIP folder
         * Set the max number as int or string as $param
         */
        V::addRule('maxFilesInZIPFolder', function ($field, $value, $params) {
            return $this->checkZipFilesNumber($params[0], $field, false);
        }, $this->_('contains more files inside a ZIP file than the maximum allowed number of %s files.'));

        /**
         * 47) Verifies that the uncompressed filesize of all files in an uploaded ZIP folder does not exceed the total file size limit.
         * Enter the max file size including unit (fe 10 MB)
         */
        V::addRule('maxTotalFileSizeZipUncompressed', function ($field, $value, $params) {
            return $this->checkZipTotalFilesizeUncompressed($field, $params[0], false);
        }, $this->_('contains a ZIP folder whose files (uncompressed) in total exceed the maximum total file size of %s.'));

        /**
         * 48) Validate if a Zip file contains all files set as $params
         * A single file could be entered as a string, multiple files have to be entered as an array
         */
        V::addRule('requiredFileNamesInZip', function ($field, $value, $params) {
            return $this->checkFilesByNameInZipFolder($field, $params[0]);
        }, $this->_('does not contain all required files inside the ZIP folder.'));

        /**
         * 49) Validate the number of ZIP files uploaded inside an upload field
         * This validation rule does only make sense on multi-upload fields
         * Set the max. number as integer or string param
         */
        V::addRule('maxNumberOfZipFolders', function ($field, $value, $params) {
            return $this->checkNumberOfZipInUploadField($field, $params[0]);
        }, $this->_('contains more ZIP folders than the allowed number of %s.'));

        /**
         * 50) Validate that a ZIP folder does not contain more subfolder levels than allowed in the hierarchy.
         * Enter the number of allowed levels as integer or string
         * 0 means that no sub-levels are allowed
         */
        V::addRule('maxDepthOfZipFolders', function ($field, $value, $params) {
            return $this->checkSubFolderLevels($field, $params[0]);
        }, $this->_('contains a ZIP folder which has more sublevel folders than the allowed number of %s.'));

        /**
         * 51) Validate if all files inside a ZIP folder are of the allowed type
         */
        V::addRule('allowedFileTypesInZipFolder', function ($field, $value, $params) {

            $zipFolders = $this->getPreparedZipData($field);

            if (empty($zipFolders)) {
                return true;
            }
            $allowedMap = $this->buildExtMap((array)($params[0] ?? []));
            if (empty($allowedMap)) {
                return true;
            }
            foreach ($zipFolders as $files) {
                foreach ($files as $file) {
                    $ext = strtolower($file['extension']);
                    if ($ext === '' || !isset($allowedMap[$ext])) {
                        return false;
                    }
                }
            }
            return true;

        }, $this->_('contains at least one file of a not allowed type.'));

        /**
         * 52) Validate if a ZIP folder does not contain a file of a not allowed file type
         */
        V::addRule('notAllowedFileTypesInZipFolder', function ($field, $value, $params) {

            $zipFolders = $this->getPreparedZipData($field);

            if (empty($zipFolders)) {
                return true;
            }
            $blockedMap = $this->buildExtMap((array)($params[0] ?? []));
            if (empty($blockedMap)) {
                return true;
            }
            foreach ($zipFolders as $files) {
                foreach ($files as $file) {
                    $ext = strtolower($file['extension']);
                    if ($ext === '' || isset($blockedMap[$ext])) {
                        return false;
                    }
                }
            }
            return true;

        }, $this->_('contains at least one file of a not allowed file type.'));

        /**
         * 53) Validate the max. filesize of an individual file inside a ZIP folder
         * Enter the filesize as fe 10 MB, 500 kB, if no unit is entered than the unit is B (Bytes)
         *
         */
        V::addRule('maxAllowedFileSizeOfFileInZipFolder', function ($field, $value, $params) {
            return $this->checkAllowedFileSizeOfFileInZipFolder($field, $params[0]);
        }, $this->_('contains at least one file that is larger than the allowed maximum filesize of %s.'));

        /**
         * 54) Validate the text against various SPAM properties to identify SPAM texts
         * Enter the degree of strictness as a parameter (0 means everything is allowed, 100 means very strict).
         * This parameter is optional. By default, a level of 50 is set, which means an average strictness
         * This validator is especially designed for textarea fields to validate for example messages in contact forms
         */
        V::addRule('checkContentForSpam', function ($field, $value, $params) {
            static $stopwords = null;
            if ($stopwords === null) {
                $config = $this->wire('modules')->getConfig('FrontendForms');
                $stopwords = isset($config['input_stopwords'])
                    ? explode("\n", $config['input_stopwords'])
                    : [];
            }

            $threshold = min(100, (int)($params[0] ?? 50));
            if ($threshold === 0) {
                return true;
            }

            return $this->calculateContentScore($value, $stopwords ?: null, $params[1] ?? null) <= (100 - $threshold);

        }, $this->_('contains parts that look like this is a SPAM text. Please avoid using the following parts in a text: more than 2 links, text less than 50 characters, too many words in capital letters, typical spam words, many exclamation marks in a row (e.g. !!!!!).'));

        /**
         * 55) Validate the all specified values inside an array (eg a multivalue field) are selected
         */
        V::addRule('array_in', function ($field, $value, $params) {

            if (!is_array($value)) return false; // must be array
            $required = is_array($params[0]) ? $params[0] : array($params[0]); // convert to array
            return empty(array_diff_key(array_flip($required), array_flip($value)));

        }, $this->_('contains not all necessary values.'));


    }

    /**
     * Internal method to check if a ZIP folder has not more ($max = true) or at least ($max = false) subfolder levels in depth
     * @param string $field
     * @param int|string $level
     * @param bool $max
     * @return bool
     */
    private function checkSubFolderLevels(string $field, int|string $level, bool $max = true): bool
    {
        $zipFolders = $this->getUploadedZipFilesForValidation($field, false);

        if (!$zipFolders) {
            return true;
        }

        $targetDepth = (int)$level;

        foreach ($zipFolders as $filename => $zipFolder) {
            $baseFolder = pathinfo($filename, PATHINFO_FILENAME);
            $baseFolderSlash = $baseFolder . '/';
            $baseFolderLen = strlen($baseFolderSlash);

            foreach ($zipFolder as $path) {
                // Normalize and find root folder
                $path = strtr($path, '\\', '/');

                $pos = strpos($path, $baseFolderSlash);
                if ($pos === false) {
                    continue;
                }

                // Extract and count depth
                $depth = substr_count($path, '/', $pos + $baseFolderLen) - 1;

                // Early exit if depth violates target
                if ($max ? $depth > $targetDepth : $depth < $targetDepth) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if files with given name are inside ($required = true) or explicitely not present ($required = false) inside a ZIP folder
     * @param string $field
     * @param array $names
     * @param bool $required
     * @return bool
     */
    private function checkFilesByNameInZipFolder(string $field, array $names, bool $required = true): bool
    {
        $zipFolders = $this->getPreparedZipData($field);

        if (!$zipFolders) {
            return true;
        }

        if (!$names) {
            return true;
        }

        // Collect all found files as associative array for O(1) lookup
        $found = [];
        foreach ($zipFolders as $zipFolder) {
            foreach ($zipFolder as $file) {
                $found[$file['basename']] = true;
            }
        }

        // Check if all required files exist
        foreach ($names as $file) {
            $exists = isset($found[$file]);
            if ($required ? !$exists : $exists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper function to check if there are not more(max) or not less(min) ZIP folders inside an upload field
     * @param string $field
     * @param int|string $number
     * @param bool $max
     * @return bool
     */
    private function checkNumberOfZipInUploadField(string $field, int|string $number, bool $max = true): bool
    {
        $count = count($this->getUploadedZipFilesForValidation($field, false));
        $number = (int)$number;

        return $max ? $count <= $number : $count >= $number;
    }

    /**
     * Check if all files inside a ZIP folder are not larger (max) or not smaller(min) than a given filesize
     * @param string $field
     * @param string $filesize
     * @param bool $max
     * @return bool
     */
    private function checkAllowedFileSizeOfFileInZipFolder(string $field, string $filesize, bool $max = true): bool
    {

        $zipFolders = $this->getPreparedZipData($field);

        if (empty($zipFolders)) {
            return true;
        }

        $limit = Inputfields::convertToBytes($filesize);

        foreach ($zipFolders as $files) {
            foreach ($files as $file) {

                if (!is_file($file['path'])) {
                    return false;
                }
                $size = $file['size'];
                if ($size === false || ($max ? $size > $limit : $size < $limit)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Internal method to check if a ZIP folder contains uncompressed at least (min) or not more (max) total filesize of all files
     * @param string $field
     * @param string $limit
     * @param bool $min
     * @return bool
     */
    private function checkZipTotalFilesizeUncompressed(string $field, string $limit, bool $min = true): bool
    {
        $zipFolders = $this->getPreparedZipData($field);

        if (!$zipFolders) {
            return true;
        }

        $limit = Inputfields::convertToBytes($limit);

        foreach ($zipFolders as $zipFolder) {
            $totalSize = 0;

            foreach ($zipFolder as $file) {
                $totalSize += $file['size'] ?? 0;
            }

            if ($min ? $totalSize < $limit : $totalSize > $limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Internal method to check if a ZIP folder contains at least (min) or not more (max) number of files
     * @param int|string $limit
     * @param string $field
     * @param bool $min
     * @return bool
     */
    private function checkZipFilesNumber(int|string $limit, string $field, bool $min = true): bool
    {
        $limit = (int)$limit;

        $zipfolders = $this->getPreparedZipData($field);

        if (!$zipfolders) {
            return true;
        }

        foreach ($zipfolders as $zipfolder) {
            if (is_array($zipfolder)) {
                $count = count($zipfolder);
                if ($min ? $count < $limit : $count > $limit) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates international BIC (Business Identifier Code)
     *
     * @param string $bic The BIC code to validate
     * @return bool true if the BIC is valid, false otherwise
     */
    private function validateBIC(string $bic): bool
    {
        // Remove spaces and convert to uppercase
        $bic = strtoupper(str_replace(' ', '', $bic));

        // BIC length must be 8 or 11 characters
        $len = strlen($bic);
        if ($len !== 8 && $len !== 11) {
            return false;
        }

        // First 4 characters must be letters (bank code)
        if (!ctype_alpha(substr($bic, 0, 4))) {
            return false;
        }

        // Characters 7-8 must be letters (country code)
        if (!ctype_alpha(substr($bic, 4, 2))) {
            return false;
        }

        // Country code validation - check if it's a valid ISO 3166-1 alpha-2 code
        $countryCode = substr($bic, 4, 2);
        if (!$this->isValidCountryCode($countryCode)) {
            return false;
        }

        // If BIC is 11 characters, characters 9-11 must be alphanumeric (branch code)
        if ($len === 11 && !ctype_alnum(substr($bic, 8, 3))) {
            return false;
        }
        // If BIC is 8 characters, verify it's a valid bank code format
        if ($len === 8) {
            // Last 2 characters should be XX (default branch) or bank-specific
            if (!ctype_alnum(substr($bic, 6, 2))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates if a country code is valid (ISO 3166-1 alpha-2)
     *
     * @param string $countryCode The country code to validate
     * @return bool true if valid, false otherwise
     */
    private function isValidCountryCode(string $countryCode): bool
    {
        static $map = null;
        $map ??= array_flip([
            'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
            'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
            'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ',
            'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET',
            'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
            'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
            'HK', 'HM', 'HN', 'HR', 'HT', 'HU',
            'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT',
            'JE', 'JM', 'JO', 'JP',
            'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
            'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
            'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
            'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ',
            'OM',
            'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
            'QA',
            'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
            'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
            'UA', 'UG', 'UM', 'US', 'UY', 'UZ',
            'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
            'WF', 'WS',
            'XK',
            'YE', 'YT',
            'ZA', 'ZM', 'ZW'
        ]);
        return isset($map[$countryCode]);
    }

    /**
     * Validate the checksum of an IBAN
     *
     * @param string $iban IBAN without spaces and already uppercase
     * @return bool
     */
    private function validateIBANChecksum(string $iban): bool
    {
        // Move first 4 chars to the end
        $iban = substr($iban, 4) . substr($iban, 0, 4);

        $remainder = 0;
        $length = strlen($iban);

        for ($i = 0; $i < $length; $i++) {

            $char = $iban[$i];

            // Numbers: append directly
            if (ctype_digit($char)) {
                $remainder = ($remainder * 10 + (int)$char) % 97;
                continue;
            }

            // Letters: A = 10 ... Z = 35
            $value = ord($char) - 55;

            // Process each digit separately to avoid huge integers
            if ($value >= 10) {
                $remainder = ($remainder * 10 + intdiv($value, 10)) % 97;
                $remainder = ($remainder * 10 + ($value % 10)) % 97;
            } else {
                $remainder = ($remainder * 10 + $value) % 97;
            }
        }

        return $remainder === 1;
    }

    /**
     * Validate international IBAN
     *
     * @param string $iban IBAN
     * @return bool true if IBAN is valid, otherwise false
     */
    private function validateIBAN(string $iban): bool
    {
        // remove empty spaces first and convert to uppercase letters
        $iban = strtoupper(str_replace(' ', '', $iban));

        // check length
        $len = strlen($iban);
        if ($len < 15 || $len > 34) {
            return false;
        }

        // Quick check: must start with 2 letters + 2 digits
        if ($len < 4 || !ctype_alpha($iban[0]) || !ctype_alpha($iban[1]) ||
            !ctype_digit($iban[2]) || !ctype_digit($iban[3])) {
            return false;
        }

        // Extract country code
        $countryCode = substr($iban, 0, 2);

        static $ibanLengths = [
            'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16,
            'BG' => 22, 'BH' => 22, 'BR' => 29, 'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28,
            'CZ' => 24, 'DE' => 22, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29, 'ES' => 24,
            'FI' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18,
            'GR' => 27, 'GT' => 28, 'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IS' => 26,
            'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28, 'LC' => 32, 'LI' => 21,
            'LT' => 20, 'LU' => 20, 'LV' => 21, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19,
            'MR' => 27, 'MT' => 31, 'MU' => 30, 'NL' => 18, 'NO' => 15, 'PK' => 24, 'PL' => 28,
            'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'RS' => 22, 'SA' => 24, 'SE' => 24,
            'SI' => 19, 'SK' => 24, 'SM' => 27, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22,
            'VG' => 24, 'XK' => 20
        ];

        // Check length for country
        if (!isset($ibanLengths[$countryCode]) || $len !== $ibanLengths[$countryCode]) {
            return false;
        }

        // Check if there are only alphanumeric characters
        if (!ctype_alnum($iban)) {
            return false;
        }

        // Validate Mod-97 Checksum
        return $this->validateIBANChecksum($iban);
    }


    /**
     * Method to calculate the score of a text depending on suspicious content
     * The higher the number, the higher the possibility that it is a spam text
     * @param string $text - the text that should be validated
     * @param array|null $spamWords - list of spam words added to the module config
     * @return int
     */
    private function calculateContentScore(string $text, ?array $spamWords, ?array $excludes): int
    {
        $score = 0;

        // Early exit for empty text
        if (empty($text)) {
            return 0;
        }

        // Convert excludes to HashMap (O(1) instead of O(n))
        $ex = $excludes ? array_flip($excludes) : [];

        // Single lowercase conversion (done once)
        $textLower = strtolower($text);
        $textLen = strlen($text);

        /**
         * 1. STOPWORDS (large list) - Highest impact check first
         */
        if (!isset($ex['stopwords'])) {
            $stopwordPath = $this->wire('config')->paths->siteModules . 'FrontendForms/stopwords.txt';
            if ($this->wire('files')->exists($stopwordPath)) {
                $count = count($this->findWords($text, $stopwordPath));
                if ($count >= 5) return 100; // Early exit
                if (($score += $count * 20) >= 100) return 100;
            }
        }

        /**
         * 2. CUSTOM WORDS
         */
        if (!isset($ex['customstopwords']) && !empty($spamWords)) {
            foreach ($spamWords as $word) {
                // Skip empty and check in one pass
                if ($word && strpos($textLower, strtolower($word)) !== false) {
                    if (($score += 20) >= 100) return 100;
                }
            }
        }

        /**
         * 3. LINKS - Quick substring count (faster than regex)
         */
        if (!isset($ex['links'])) {
            // Cache both counts in one operation
            $httpCount = substr_count($textLower, 'http://') + substr_count($textLower, 'https://');
            if ($httpCount > 2) {
                if (($score += 20) >= 100) return 100;
            }
        }

        /**
         * 4. CAPITAL LETTERS - Optimized single pass
         */
        if (!isset($ex['capitalletters']) && $textLen > 0) {
            $letters = $upper = 0;

            // Use isset() instead of checking ranges - faster
            for ($i = 0; $i < $textLen; $i++) {
                $ord = ord($text[$i]);
                // A-Z: 65-90, a-z: 97-122
                if (($ord >= 65 && $ord <= 90) || ($ord >= 97 && $ord <= 122)) {
                    $letters++;
                    if ($ord >= 65 && $ord <= 90) $upper++;
                }
            }

            if ($letters > 0 && ($upper / $letters) > 0.5) {
                if (($score += 15) >= 100) return 100;
            }
        }

        /**
         * 5. REPEATED CHARS - Early exit optimization
         */
        if (!isset($ex['repeatedchars'])) {
            // Check most common patterns first
            if (strpos($text, '!!') !== false ||
                strpos($text, '??') !== false ||
                strpos($text, '$$') !== false ||
                strpos($text, '##') !== false) {
                if (($score += 10) >= 100) return 100;
            }
        }

        /**
         * 6. EXCLAMATIONS
         */
        if (!isset($ex['exclamations'])) {
            if (substr_count($text, '!') > 5) {
                if (($score += 10) >= 100) return 100;
            }
        }

        /**
         * 7. LENGTH CHECK - Only if score > 0
         */
        if (!isset($ex['length']) && $score > 0 && $textLen < 50) {
            $score += 10;
        }

        return min($score, 100);
    }

    private function findWords(string $text, string $filePath, int $chunkSize = 1000): array
    {
        static $patterns = [];

        // Cache pattern per file
        if (!isset($patterns[$filePath])) {

            if (!file_exists($filePath)) {
                throw new Exception("Datei nicht gefunden: " . $filePath);
            }

            $words = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($words === false) {
                throw new Exception("Datei konnte nicht gelesen werden: " . $filePath);
            }

            // clean up
            $words = array_map('trim', $words);
            $words = array_filter($words);

            // Regex escape
            $escaped = array_map(
                static fn($w) => preg_quote($w, '/'),
                $words
            );

            // Split large lists into chunks
            $chunks = array_chunk($escaped, $chunkSize);

            $patterns[$filePath] = array_map(
                static function ($chunk) {
                    return '/\b(' . implode('|', $chunk) . ')\b/iu';
                },
                $chunks
            );
        }

        $matchesFound = [];

        foreach ($patterns[$filePath] as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                array_push($matchesFound, ...$matches[1]);
            }
        }

        // Remove duplicates
        return array_values(array_unique($matchesFound));
    }

    /**
     * Check if a file with the same name exists in the destination directory
     * If param is set to true, the filename will be overwritten with a timestamp and the output is true
     * Otherwise output is false
     * @param array $value
     * @param string $uploadPath
     * @param $param
     * @return bool
     * @throws WireException
     */
    private function checkDuplicateFilename(array $value, string $uploadPath, $param): bool
    {

        $singleFile = (isset($value[0]) && is_array($value[0]));
        $filename = $singleFile ? $value[0]['name'] : $value['name'];
        // sanitize filename
        $filename = strtolower($this->wire('sanitizer')->filename($filename));

        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $exist = $this->wire('files')->exists($uploadPath . $filename);

        if ($exist && $param && $param[0] === true) {
            // overwrite the filename
            $newFileNamePath = $uploadPath . $basename . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $this->wire('files')->rename($uploadPath . $filename, $newFileNamePath);
            $this->storedFiles[] = $newFileNamePath;
            return true;
        } else {
            $this->storedFiles[] = $uploadPath . $filename;
            return !$exist;
        }
    }

    /**
     * Helper function for allowedFileTypesInZipFolder / notAllowedFileTypesInZipFolder validator
     * @param array $extensions
     * @return array
     */
    private function buildExtMap(array $extensions): array
    {
        $map = [];
        foreach ($extensions as $ext) {
            $ext = ltrim(strtolower($ext), '.');
            if ($ext !== '') {
                $map[$ext] = true;
            }
        }
        return $map;
    }

    /**
     * Private method to check if an entered date is within or outside a given time range
     * @param string|null $date_1
     * @param string $value
     * @param int $days
     * @param bool $within
     * @return bool
     * @throws WireException
     * @throws Exception
     */
    private function checkDateRange(string|null $date_1, string $value, int $days, bool $within = true): bool
    {
        $days = $this->wire('sanitizer')->intSigned($days);
        $date_1 = $this->getValue($date_1);

        if (!$date_1) {
            return true;
        }

        $date_1 = $this->wire('datetime')->date('Y-m-d', $this->wire('datetime')->strtotime($date_1));
        $date_2 = new DateTime($date_1);

        if ($days > 0) {
            $date_2->add(new DateInterval('P' . $days . 'D'));
            $date_2 = $date_2->format('Y-m-d');
            return $within ? ($value <= $date_2 && $value > $date_1) : ($value > $date_2);
        }

        if ($days < 0) {
            $date_2->sub(new DateInterval('P' . abs($days) . 'D'));
            $date_2 = $date_2->format('Y-m-d');
            return $within ? ($value >= $date_2 && $value < $date_1) : ($value < $date_2);
        }

        return false;
    }

    /**
     * Private function to compare dates if one date is before or after another date
     * @param string|null $date_1
     * @param string $date_2
     * @param bool $before
     * @return bool
     * @throws WireException
     */
    private function compareDates(string|null $date_1, string $date_2, bool $before = true): bool
    {
        static $dateTime = null;
        $dateTime ??= $this->wire('datetime');
        $ts1 = $dateTime->strtotime($date_1);
        $ts2 = $dateTime->strtotime($date_2);
        return $before ? ($ts2 < $ts1) : ($ts2 > $ts1);
    }


    /**
     * Get the id of the form where the field is included
     * @return string|bool
     * @throws WireException
     */
    protected function getFormId(): string|bool
    {
        $method = strtolower($this->wire('input')->requestMethod());
        foreach ($this->wire('input')->$method() as $key => $value) {
            if (str_ends_with($key, '-form_id')) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Enter a field name, check if form id prefix is present, otherwise add it
     * @param string $field_name
     * @return string
     * @throws WireException
     */
    protected function getFieldName(string $field_name): string
    {

        $form_id = $this->getFormId();
        if (!$form_id) {
            throw new Exception("ID of form is missing!");
        }
        // check if form id is used as the pre-fix of the field name
        if (!str_starts_with($field_name, $form_id . '-')) {
            // prefix is not added, so add it to the field name
            $field_name = $form_id . '-' . $field_name;
        }
        return $field_name;
    }

    /**
     * Set a session variable which contains the email/username and the value of the password entered
     * @param string $fieldName -> the name of the username or email field
     * @param string $logintype -> could be either username or email
     * @param string $value -> the field value
     * @return void
     * @throws WireException
     * @throws WirePermissionException
     */
    protected function createSessionForLoginAttempts(string $fieldName, string $logintype, string $value): void
    {
        $existing = $this->wire('session')->get($fieldName) ?? [];
        // hash the password for security reasons
        $value = password_hash($value, PASSWORD_DEFAULT);
        $existing[] = [$logintype => $value];
        $this->wire('session')->set($fieldName, $existing);
    }

    /**
     * Prepare and cache ZIP validation data
     *
     * Structure:
     * [
     *   'zipname.zip' => [
     *      [
     *          'path' => '',
     *          'normalized' => '',
     *          'basename' => '',
     *          'extension' => '',
     *          'size' => 1234,
     *          'depth' => 2
     *      ]
     *   ]
     * ]
     */
    private function getPreparedZipData(string $field): array
    {
        // Return cached data
        if (isset($this->zipValidationCache[$field])) {
            return $this->zipValidationCache[$field];
        }

        $zipFolders = (array)$this->getUploadedZipFilesForValidation($field, false);

        if (empty($zipFolders)) {
            return $this->zipValidationCache[$field] = [];
        }

        $prepared = [];

        foreach ($zipFolders as $zipName => $files) {

            $items = [];

            foreach ($files as $file) {

                $normalized = strtr($file, '\\', '/');

                // get real type of file
                $mimeType = false;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = finfo_file($finfo, $normalized);
                    finfo_close($finfo);
                }

                $items[] = [
                    'path' => $file,
                    'normalized' => $normalized,
                    'basename' => basename($normalized),
                    'extension' => strtolower(pathinfo($normalized, PATHINFO_EXTENSION)),
                    'size' => is_file($file) ? (filesize($file) ?: 0) : 0,
                    'depth' => substr_count($normalized, '/'),
                    'mime' => $mimeType
                ];
            }

            $prepared[$zipName] = $items;
        }

        return $this->zipValidationCache[$field] = $prepared;
    }

}

