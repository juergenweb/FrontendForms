<?php
declare(strict_types=1);

/*
 * Class containing all custom validators
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: CustomRules.php
 * Created: 25.02.2023 
 */


namespace FrontendForms;

use ProcessWire\User;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;
use Valitron\Validator as V;

class CustomRules extends Tag
{

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

        /**
         * 1) Check if username is unique
         */
        V::addRule('uniqueUsername', function ($field, $value) {
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
        }, $this->_('must be unique. This username is already in use. Please choose another username.'));


        /**
         * 2) Check if username and password match
         */
        V::addRule('matchUsername', function ($field, $value, array $params) {
            $fieldName = getFieldName($params[0]); // get field name including form id prefix

            $username = $this->wire('input')->$fieldName;
            $username = $this->wire('sanitizer')->pageName($username); // important
            $u = $this->wire('users')->get($username);
            if ($u->id && $u->pass->matches($value)) {
                return true;
            }
            return false;
        }, $this->_('and username do not match.'));


        /**
         * 3) Check if password meets the conditions set in the backend
         * fe. 8 characters long, must contain number, must contain upper and lowercase...
         * Add field name as first param if field name is not pass
         */
        V::addRule('meetsPasswordConditions', function ($field, $value, array $params) {
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


        /**
        * 4) Check if username contains only the following characters: a-z0-9-_
        */
        V::addRule('usernameSyntax', function ($field, $value) {
            $regex = '/[a-z\d\-_.]$/';
            if (preg_match($regex, $value)) {
                return true;
            }
            return false;
        }, $this->_('contains not allowed characters. Please use only lowercase digits, numbers, underscore, periods and hyphen (no whitespaces).'));


        /**
         * 5) Check if email is unique
         */
        V::addRule('uniqueEmail', function ($field, $value) {
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
        V::addRule('checkPasswordOfUser', function ($field, $value, array $params) {
            $user = $params[0];
            if ($user instanceof User) {
                return $user->pass->matches($value);
            }
            return false;
        }, $this->_('entered is not the same password as the one, that is stored in the database.'));


        /**
        * 7) Check if email and password match
        */
        V::addRule('matchEmail', function ($field, $value, array $params) {
            $fieldName = $this->getFieldName($params[0]); // get field name including form id prefix
            $email = $this->wire('input')->$fieldName;
            $u = $this->wire('users')->get('email=' . $email);
            if (($u->id != 0) && ($u->pass->matches($value))) {
                return true;
            }
            return false;
        }, $this->_('and email do not match.'));


        /**
        * 8) Check if value is boolean true
        */
        V::addRule('isBooleanAndTrue', function ($field, $value) {
            if (is_bool($value)) {
                if ($value) {
                    return true;
                }
                return false;
            }
            return false;
        }, $this->_('is not Boolean true.'));


        /**
        * 9) Check if value is boolean false
        */
        V::addRule('isBooleanAndFalse', function ($field, $value) {
            return !(is_bool($value) && $value);
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
        V::addRule('safePassword', function ($field, $value) {
            $passwords = $this->wire('modules')->getConfig('FrontendForms')['input_blacklist'];
            $passwords = explode("\n", $passwords);
            if (!$passwords) {
                return true;
            } // no passwords in the blacklist -> return tre
            return (!in_array($value, $passwords)); // check if password is in the blacklist -> false, otherwise true
        },
            $this->_('value is in the list of the most popular passwords and therefore not save. Please select another one.'));

        /**
         * 16) Check if the uploaded file is not larger than the allowed filesize
         */
        V::addRule('allowedFileSize', function ($field, $value, array $params) {
            if (count($value) == 1) {
                if ($value[0]['error'] == '0') {
                    $size = $value[0]['size'];
                    return ($size <= $params[0]);
                }
            } else {
                foreach ($value as $file) {
                    if ($file['error'] == '0') {
                        $size = $file['size'];
                        return ($size <= $params[0]);
                    }
                    return true;
                }
            }
            return true;
        }, $this->_('is larger than the allowed filesize of %s.'));


        /**
         * 17) Check if an error occur during the upload of the file
         */
        V::addRule('noErrorOnUpload', function ($field, $value) {
            if (count($value) == 1) {
                $error = $value[0]['error'];
                if (($error == '4') || ($error == '0')) {
                    return true;
                }
            } else {
                foreach ($value as $file) {
                    if (($file['error'] == '4') || ($file['error'] == '0')) {
                        return true;
                    }
                    return $file['error'];
                }
            }
            return false;
        }, $this->_('caused an error during the upload.'));


        /**
         * 18) Check if uploaded files are of the allowed extensions
         */
        V::addRule('allowedFileExt', function ($field, $value, array $params) {
            if (count($value) == 1) {
                if ($value[0]['error'] == '0') {
                    $type = strtolower(pathinfo($value[0]['name'], PATHINFO_EXTENSION));
                    return (in_array($type, $params[0]));
                }
            } else {
                foreach ($value as $file) {
                    if ($file['error'] == '0') {
                        $type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        return (in_array($type, $params[0]));
                    }
                    return true;
                }
            }
            return true;
        }, $this->_('does not belong to the allowed file types: %s.'));


        /**
         * 19) Check if the uploaded file is not larger than the allowed filesize as declared inside the php.ini
         */
        V::addRule('phpIniFilesize', function ($field, $value) {
            if (count($value) == 1) {
                if ($value[0]['error'] == '0') {
                    $size = $value[0]['size'];
                    return ($size <= ((int)ini_get("upload_max_filesize") * 1024));
                }
            } else {
                foreach ($value as $file) {
                    if ($file['error'] == '0') {
                        $size = $file['size'];
                        return ($size <= ((int)ini_get("upload_max_filesize") * 1024));
                    }
                    return true;
                }
            }
            return true;
        }, $this->_('is larger than the max. allowed filesize.'));

        /**
         * 20) Check if uploaded files are not of the forbidden extensions
         * This is the opposition of the allowedFileExt
         */
        V::addRule('forbiddenFileExt', function ($field, $value, array $params) {
            if (count($value) == 1) {
                if ($value[0]['error'] == '0') {
                    $type = strtolower(pathinfo($value[0]['name'], PATHINFO_EXTENSION));
                    return (!in_array($type, $params[0]));
                }
            } else {
                foreach ($value as $file) {
                    if ($file['error'] == '0') {
                        $type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        return (!in_array($type, $params[0]));
                    }
                    return true;
                }
            }
            return true;
        }, $this->_('is of one of the forbidden file types: %s.'));

        /**
         * 21) Check if entered string is a valid time
         */
        V::addRule('time', function ($field, $value) {
            if ($value) {
                $time_regex = '#^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$#'; //regex for 24-hour format
                return (preg_match($time_regex, $value));
            }
            return true;

        }, $this->_('is not a valid time.'));


    }


    /**
     * Get the id of the form where the field is included
     * @return string|bool
     * @throws WireException
     */
    protected function getFormId():string|bool
    {

        $request_method = strtolower($this->wire('input')->requestMethod());
        $values = $this->wire('input')->$request_method();
        // grab the form key containing the form id
        $id = '';
        foreach ($values as $key => $fieldvalue) {
            $needle = substr($key, -8);
            if ($needle == '-form_id') {
                $id = $fieldvalue;
                break;
            }
        }
        // if id is not present, what should not be, set it to false in any case
        if ($id == '') {
            return false;
        }
        return $id;
    }

    /**
     * Enter a field name, check if form id prefix is present, otherwise add it
     * @param string $field_name
     * @return string
     * @throws WireException
     */
    protected function getFieldName(string $field_name):string
    {
        $form_id = $this->getFormId();
        // check if form id is used as the pre-fix of the field name
        if (!substr($field_name, strlen($form_id))) {
            // prefix is not added, so add it to the field name
            $field_name = $form_id . '-' . $field_name;
        }
        return $field_name;
    }


}
