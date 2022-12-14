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
        if ($this->wire('user')->email == $value) {
            return true;
        }
    }
    if ($this->wire('users')->get('email=' . $value)->id == '0') {
        return true;
    }
    return false;
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
    $passwords =  $this->wire('modules')->getConfig('FrontendForms')['input_blacklist'];
    $passwords = explode("\n", $passwords);
    if(!$passwords)
        return true; // no passwords in the blacklist -> return tre
    return (!in_array($value, $passwords)); // check if password is in the blacklist -> false, otherwise true
}, $this->_('value is in the list of the most popular passwords and therefore not save. Please select another one.'));