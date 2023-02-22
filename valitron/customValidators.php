<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Additional custom validation rules for the Valitron library
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: customValidators.php
 * Created: 03.07.2022
 */

use ProcessWire\User as User;

// 1) Check if username is unique
$this->Validator::addRule('uniqueUsername', function ($value) {
    // check if user is logged in
    if ($this->wire('user')->isLoggedin()) {
        if ($this->wire('user')->name === $value) {
            return true;
        }
    }
    $value = $this->wire('sanitizer')->pageName($value);
    if ($this->wire('users')->get($value)->id !== 0) {
        return false;
    }
    return true;
}, $this->_('must be unique. This username is already in use. Please choose another username'));


// 2) Check if username and password match
$this->Validator::addRule('matchUsername', function ($field, $value, array $params) {
    $fieldName = $params[0];
    $username = $this->wire('input')->$fieldName;
    $username = $this->wire('sanitizer')->pageName($username); // important
    $u = $this->wire('users')->get($username);
    if ($u->id && $u->pass->matches($value)) {
        return true;
    }
    return false;
}, $this->_('and username do not match'));


/*
* 3) Check if password meets the conditions set in the backend
* fe. 8 characters long, must contain number, must contain upper and lowercase...
* Add field name as first param if field name is not pass
*/
$this->Validator::addRule('meetsPasswordConditions', function ($field, $value, array $params) {
// grab the InputfieldPassword object
    $passwordModule = $this->wire('modules')->get('InputfieldPassword');
// get the field object
    $fieldName = (!empty($params)) ? $params[0] : 'pass';
    $passwordField = $this->wire('fields')->get($fieldName);
// set requirements of field to InputfieldPassword
    if (!$passwordField->requirements) {
// if no requirements are stored in the database, take the default data from the inputfield default configuration
        $requirements = $this->wire('modules')->get('InputfieldPassword')->requirements;
    } else {
        $requirements = $passwordField->requirements;
    }
    $passwordModule->set('requirements', $requirements);
// validate if requirements meet
    return $passwordModule->isValidPassword($value);

}, $this->_('does not meet the conditions.'));


/*
* 4) Check if username contains only the following characters: a-z0-9-_
*/
$this->Validator::addRule('usernameSyntax', function ($field, $value) {
    $regex = '/[a-z\d\-_.]$/';
    if (preg_match($regex, $value)) {
        return true;
    }
    return false;
},
    $this->_('contains not allowed characters. Please use only lowercase digits, numbers, underscore, periods and hyphen (no whitespaces).'));


// 5) Check if email is unique
$this->Validator::addRule('uniqueEmail', function ($value) {
    // check if user is logged in
    if ($this->wire('user')->isLoggedin()) {
        // user is logged in
        if ($this->wire('user')->email == $value) {
            return true;
        }
    }
    $user = $this->wire('users')->get('email=' . $value);
    if ($user) {
        if ($user->id != 0) {
            return false;
        }
    }
    return true;
}, $this->_('must be unique. This email is already in use. Please use another email address.'));

/**
 * 6) Check if entered password is the correct password stored in the DB for the current user
 * This is useful fe if the user has to fill in the old password before he enters the new one
 * As param enter the user object
 * $field->setRule('checkPasswordOfUser', $user);
 */
$this->Validator::addRule('checkPasswordOfUser', function ($field, $value, array $params) {
    $user = $params[0];
    if ($user instanceof User) {
        return $user->pass->matches($value);
    }
    return false;
}, $this->_('entered is not the same password as the one, that is stored in the database.'));


/*
* 7) Check if email and password match
*/
$this->Validator::addRule('matchEmail', function ($field, $value, array $params) {
    $fieldName = $params[0];
    $email = $this->wire('input')->$fieldName;
    $u = $this->wire('users')->get('email=' . $email);
    if (($u->id != 0) && ($u->pass->matches($value))) {
        return true;
    }
    return false;
}, $this->_('and email do not match'));


/*
* 8) Check if value is boolean true
*/
$this->Validator::addRule('isBooleanAndTrue', function ($field, $value) {
    if (is_bool($value)) {
        if ($value) {
            return true;
        }
        return false;
    }
    return false;
}, $this->_('is not Boolean true'));


/*
* 9) Check if value is boolean false
*/
$this->Validator::addRule('isBooleanAndFalse', function ($field, $value) {
    return !(is_bool($value) && $value);
}, $this->_('is not Boolean false'));


/*
* 10) Check if the value is exact the same value as entered as param
*/
$this->Validator::addRule('exactValue', function ($field, $value, array $params) {
    return ($value === $params[0]);
}, $this->_('does not have the expected value'));


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
$this->Validator::addRule('checkTfaCode', function ($field, $value, array $params) {
    $user = $params[0]; // user object
    $module = $params[1]; // module object
    $className = $module->className(true);
// start instance
    $tfa = new $className();
    return $tfa->isValidUserCode($user, $value, $module->getUserSettings($user));
}, $this->_('is not correct'));


/**
 * 12) Check if the value entered is different from another value entered as param
 * $field->setRule('differentValue', 'test'); // entered value must be different from "test" to return true
 */
$this->Validator::addRule('differentValue', function ($field, $value, array $params) {
    return ($value !== $params[0]);
}, $this->_('entered must be different than the value specified.'));


/**
 * 13) Check if the new password entered is different the old one
 * $field->setRule('differentPassword', $user); // entered value must be different from "test" to return true
 */
$this->Validator::addRule('differentPassword', function ($field, $value, array $params) {
    if ($params[0]->pass->matches($value)) {
        return false;
    }
    return true;
}, $this->_('entered must be different from the old password.'));


/**
 * 14) Check if captcha value is correct
 * This is a special validator for the integrated captcha field
 */
$this->Validator::addRule('checkCaptcha', function ($field, $value, array $params) {
    return ($value === $params[0]);
}, $this->_('value entered is not correct.'));

/**
 * 15) Check if password is safe - in other words: is not in the list of the most popular passwords
 */
$this->Validator::addRule('safePassword', function ($field, $value) {
    $passwords = $this->wire('modules')->getConfig('FrontendForms')['input_blacklist'];
    $passwords = explode("\n", $passwords);
    if (!$passwords) {
        return true;
    } // no passwords in the blacklist -> return tre
    return (!in_array($value, $passwords)); // check if password is in the blacklist -> false, otherwise true
}, $this->_('value is in the list of the most popular passwords and therefore not save. Please select another one.'));

/**
 * 16) Check if the uploaded file is not larger than the allowed filesize
 */
$this->Validator::addRule('allowedFileSize', function ($field, $value, array $params) {
    if (count($value) == count($value, COUNT_RECURSIVE)) {
        // one dimensional array = single upload
        $value = [$value];
    }
    foreach ($value as $file) {
        if (($file['error'] == '4') || ($file['error'] == '0')) {
            return true;
        }
        return ($file['size'] <= $params[0]);
    }
}, $this->_('is larger than the allowed filesize of %s.'));


/**
 * 17) Check if an error occur during the upload of the file
 */
$this->Validator::addRule('noErrorOnUpload', function ($field, $value) {

    if (count($value) == count($value, COUNT_RECURSIVE)) {
        // one dimensional array = single upload

        $value = array_filter([$value]);
    }

    foreach ($value as $file) {
        if (($file['error'] == '4') || ($file['error'] == '0')) {
            return true;
        }
        return $file['error'];
    }
    return true;
}, $this->_('caused an error during the upload.'));

/**
 * 18) Check if uploaded files are of the allowed extensions
 */
$this->Validator::addRule('allowedFileExt', function ($field, $value, array $params) {
    // check if single or multiple file upload
    if ($value) {
        if (count($value) == count($value, COUNT_RECURSIVE)) {
            // one dimensional array = single upload
            $value = [$value];
        }
        $allowedExt = array_map('strtolower', $params[0]); // convert all values to lowercase
        foreach ($value as $file) {
            if (($file['error'] == '4') || ($file['error'] == '0')) {
                return true;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // convert value to lowercase
            return (in_array($ext, $allowedExt));
        }
    }
    return true;
}, $this->_('does not belong to the allowed file types: %s'));


/**
 * 20) Check if the uploaded file is not larger than the allowed filesize as declared inside the php.ini
 */
$this->Validator::addRule('phpIniFilesize', function ($field, $value) {
    if ($value) {
        if (count($value) == count($value, COUNT_RECURSIVE)) {
            // one dimensional array = single upload
            $value = [$value];
        }
        foreach ($value as $file) {
            if (($file['error'] == '4') || ($file['error'] == '0')) {
                return true;
            }
            return ($file['size'] <= ((int)ini_get("upload_max_filesize") * 1024));
        }
    }
    return true;
}, $this->_('is larger than the max. allowed filesize.'));

/**
 * 21) Check if uploaded files are not of the forbidden extensions
 * This is the opposition of the allowedFileExt
 */
$this->Validator::addRule('forbiddenFileExt', function ($field, $value, array $params) {
    // check if single or multiple file upload
    if (count($value) == count($value, COUNT_RECURSIVE)) {
        // one dimensional array = single upload
        $value = [$value];
    }
    $forbiddenExt = array_map('strtolower', $params[0]); // convert all values to lowercase
    foreach ($value as $file) {
        if ($file['name']) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // convert value to lowercase
            return (!in_array($ext, $forbiddenExt));
        } else {
            return true;
        }
    }
}, $this->_('is of one of the forbidden file types: %s'));

/**
 * Check if entered string is a valid time
 */
$this->Validator::addRule('time', function ($value) {
    if ($value) {
        $time_regex = '#^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$#'; //regex for 24-hour format
        return (preg_match($time_regex, $value));
    }
    return true;

}, $this->_('is not a valid time.'));
