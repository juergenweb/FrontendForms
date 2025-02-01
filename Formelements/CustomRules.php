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
                $fieldName = $this->getFieldName($params[0]); // get field name including form id prefix

                $username = $this->wire('input')->$fieldName;
                $username = $this->wire('sanitizer')->pageName($username); // important
                $u = $this->wire('users')->get($username);
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
             * 4) User name may contain lowercase a-z, 0-9, hyphen or underscore
             */
            V::addRule('usernameSyntax', function ($field, $value) {
                $regex = '/[a-z\d\-_]$/';
                if (preg_match($regex, $value)) {
                    return true;
                }
                return false;
            },
                $this->_('contains not allowed characters. User name may contain lowercase a-z, 0-9, hyphen or underscore (no whitespaces).'));


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
                        return ($size <= Inputfields::convertToBytes($params[0]));
                    }
                } else {
                    foreach ($value as $file) {
                        if ($file['error'] == '0') {
                            $size = $file['size'];
                            return ($size <= Inputfields::convertToBytes($params[0]));
                        }
                        return true;
                    }
                }
                return true;
            }, $this->_('is larger than the allowed filesize.'));


            /**
             * 17) Check if an error occured during the upload of the file
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

            }, $this->_('is not a valid time. You have to enter the time in this format: HH:MM:SS (fe. 19:00:00)'));


            /**
             * 22) Check if entered string is a valid month string
             * Format: YYYY-MM fe 2023-03
             */
            V::addRule('month', function ($field, $value) {

                if ($value) {
                    $month_regex = '#^\d{4}-(0[1-9]|1[012])$#'; //regex month format
                    return (preg_match($month_regex, $value));
                }
                return true;

            },
                $this->_('is not a valid month. You have to enter the month in this format including the year: YYYY-MM (fe. 2023-06)'));


            /**
             * 23) Check if entered string is a valid week string
             * Format: YYYY-Www fe 2023-W25
             */
            V::addRule('week', function ($field, $value) {

                if ($value) {
                    $week_regex = '#^\d{4}-W(0[1-9]|1[053])$#'; //regex for week format
                    return (preg_match($week_regex, $value));
                }
                return true;

            },
                $this->_('is not a valid week. You have to enter the week in this format including the year: YYYY-Www (fe. 2023-W06)'));

            /**
             * 24) Check if entered string is a valid hexadecimal color code
             * Format: #666 or #666666
             */
            V::addRule('checkHex', function ($field, $value) {

                if ($value) {
                    $hex_regex = '/#([a-fA-F0-9]{3}){1,2}\b/'; //regex for week format
                    return (preg_match($hex_regex, $value));
                }
                return true;

            }, $this->_('is not a valid hexadecimal color code.'));

            /**
             * 25) Check if a date is before a date entered in another field
             */
            V::addRule('dateBeforeField', function ($field, $value, array $params) {
                $reference_date_field = $params[0]; // name of the reference field
                $date_1 = $this->getValue($reference_date_field);
                if ($date_1) {
                    return $this->compareDates($date_1, $value);
                }
                return true;
            }, $this->_('must be before %s.'));

            /**
             * 26) Check if a date is after a date entered in another field
             */
            V::addRule('dateAfterField', function ($field, $value, array $params) {
                $reference_date_field = $params[0]; // name of the reference field
                $date_1 = $this->getValue($reference_date_field);
                if ($date_1) {
                    return $this->compareDates($date_1, $value, false);
                }
                return true;
            }, $this->_('must be after %s.'));

            /**
             * 27) Check if a date is within a given timerange in days depending on another field
             */
            V::addRule('dateWithinDaysRange', function ($field, $value, array $params) {
                return $this->checkDateRange($params[0], $value, $params[1]);
            }, $this->_('must be within the allowed time range.'));

            /**
             * 28) Check if a date is outside a given timerange in days depending on another field
             */
            V::addRule('dateOutsideOfDaysRange', function ($field, $value, array $params) {
                return $this->checkDateRange($params[0], $value, $params[1], false);
            }, $this->_('must be outside the forbidden time range.'));


            /**
             * 29) Check international first and last name
             * Based on https://regexpattern.com/international-first-last-names/
             */
            V::addRule('firstAndLastname', function ($field, $value) {
                $pattern = '/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.\'-]+$/u';
                return preg_match($pattern, $value);
            }, $this->_('contains invalid characters for a name.'));


            /**
             * 30) Check if the filename of an uploaded file is unique inside the upload directory
             */
            V::addRule('uniqueFilenameInDir', function ($field, $value, $param) {
                // only a single file has been uploaded
                if (count($value) === 1) {
                    return $this->checkDuplicateFilename($value, $this->uploadPath, $param);
                } else {
                    // multiple files have been uploaded
                    $results = [];
                    foreach ($value as $file) {
                        $results[] = $this->checkDuplicateFilename($file, $this->uploadPath, $param);
                    }
                    if (in_array(false, $results)) {
                        return false;
                    }
                    return true;
                }
                return false;
            }, $this->_('contains a file that has the same file name as a file stored in the destination directory.'));

            /**
             * 31) Check if text is inside an array of various texts
             */
            V::addRule('compareTexts', function ($field, $value, $params) {

                // check if value is array first
                if (!is_array($params[0]))
                    throw new \Exception($this->_('Please add only an array as second parameter.'));

                if ($params[0]) {

                    $answers = array_map('strtolower', $params[0]);
                    // convert the value to lowercase too
                    $value = strtolower($value);
                    if (in_array($value, $answers)) {
                        return true;
                    }
                    return false;
                }
                return true;
            }, $this->_('contains not the correct answer.'));


            /**
             * 32) Check if IBAN is of the correct syntax
             * This validator is taken from cakephp 3.7 validation class.
             */
            V::addRule('checkIban', function ($field, $value) {
                $check = $value;
                if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $check)) {
                    return false;
                }

                $country = substr($check, 0, 2);
                $checkInt = intval(substr($check, 2, 2));
                $account = substr($check, 4);
                $search = range('A', 'Z');
                $replace = [];
                foreach (range(10, 35) as $tmp) {
                    $replace[] = strval($tmp);
                }
                $numStr = str_replace($search, $replace, $account . $country . '00');
                $checksum = intval(substr($numStr, 0, 1));
                $numStrLength = strlen($numStr);
                for ($pos = 1; $pos < $numStrLength; $pos++) {
                    $checksum *= 10;
                    $checksum += intval(substr($numStr, $pos, 1));
                    $checksum %= 97;
                }

                return ((98 - $checksum) === $checkInt);
            }, $this->_('is not in the correct format.'));

            /**
             * 33) Check if BIC code is in the right format
             */
            V::addRule('checkBic', function ($field, $value) {
                $pattern = '/^[a-z]{6}[0-9a-z]{2}([0-9a-z]{3})?\z/i';
                return preg_match($pattern, $value);
            }, $this->_('is not in the correct format.'));


            /**
             * 34) Check if the x and y positions of the slider CAPTCHA are correct
             * This validator is only for internal usage on the slider CAPTCHA to provide
             * server-side validation too.
             */
            V::addRule('checkSliderCaptcha', function ($field, $value, $params) {

                $xPos = $params[0] ?? false;
                $yPos = $params[1] ?? false;
                $id =  $params[2] ?? false;

                $sessionXPos = $this->wire('session')->get($id.'-captcha_x') ?? false;
                $sessionYPos = $this->wire('session')->get($id.'-captcha_y') ?? false;

                $xError = abs($sessionXPos - $xPos);
                $yError = abs($sessionYPos - $yPos);

                return ($xPos !== false && $yPos !== false && $sessionXPos !== false && $sessionYPos !== false && $xError < 0.0001 && $yError < 0.0001);

            }, $this->_('has not been solved correctly.'));


            /**
             * 35) Check if the value entered is a correct cyrillic name
             */
            V::addRule('cyrillicName', function ($field, $value, $params) {
                $regex = '/[^а-яё\-]/iu';
                if (!preg_match($regex, $value)) {
                    return true;
                }
                return false;
            }, $this->_('contains not allowed characters. Cyrillic name may contain lowercase and uppercase а-я and hyphen (no whitespaces).'));

        }


        /**
         * Check if a file with the same name exists in the destination directory
         * If param is set to true, the filename will be overwritten with a timestamp and the output is true
         * Otherwise output is false
         * @param array $value
         * @param string $uploadPath
         * @param $param
         * @return bool
         * @throws \ProcessWire\WireException
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

            $storedFiles = $this->storedFiles;
            if ($exist && $param && $param[0] === true) {
                // overwrite the filename
                $newFileNamePath = $uploadPath . $basename . '-' . time() . '.' . $ext;
                $this->wire('files')->rename($uploadPath . $filename, $newFileNamePath);
                array_push($this->storedFiles, $newFileNamePath);
                return true;
            } else {
                array_push($this->storedFiles, $uploadPath . $filename);
                return !$exist;
            }
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

            if ($date_1) {
                // converting $date_1 to timestring
                $date_1 = $this->wire('datetime')->strtotime($date_1);
                // converting it to Y-m-d format
                $date_1 = $this->wire('datetime')->date('Y-m-d', $date_1);

                // calculating the new date
                $date_2 = new DateTime($date_1);

                if ($days > 0) {
                    // value is in the future
                    $date_2->add(new DateInterval('P' . $days . 'D')); // P1D means a period of 1 day
                    $date_2 = $date_2->format('Y-m-d');
                    if ($within) {
                        // date must be within the given time range
                        return (($value <= $date_2) && ($value > $date_1));
                    } else {
                        // date must be after the given time range
                        return ($value > $date_2);
                    }
                } else if ($days < 0) {
                    // value is in the past
                    $days = $days * (-1);
                    $date_2->sub(new DateInterval('P' . $days . 'D'));
                    $date_2 = $date_2->format('Y-m-d');
                    if ($within) {
                        return (($value >= $date_2) && ($value < $date_1));
                    } else {
                        // date must be before the given time range
                        return ($value < $date_2);
                    }
                } else {
                    // value is 0
                    return false;
                }
            }
            return true;
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
            // converting $date_1 to timestring
            $date_1 = $this->wire('datetime')->strtotime($date_1);
            // converting it to Y-m-d format for comparison
            $date_1 = $this->wire('datetime')->date('Y-m-d', $date_1);

            // converting $date_2 to timestring
            $date_2 = $this->wire('datetime')->strtotime($date_2);
            // converting it to Y-m-d format for comparison
            $date_2 = $this->wire('datetime')->date('Y-m-d', $date_2);
            // check if the date 2 is before date 1
            if ($before) {
                return ($date_2 < $date_1);
            }
            return ($date_2 > $date_1);
        }


        /**
         * Get the id of the form where the field is included
         * @return string|bool
         * @throws WireException
         */
        protected function getFormId(): string|bool
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
        protected function getFieldName(string $field_name): string
        {
            $form_id = $this->getFormId();
            // check if form id is used as the pre-fix of the field name
            if (!substr($field_name, strlen($form_id))) {
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
            // combinations were taken, to fe lock the user account due to security.
            if ($this->wire('session')->get($fieldName)) {
                // session exists so get the value
                $session_value = $this->wire('session')->get($fieldName);
                // add new value to the session variable
                $session_value[] = [$logintype => $value];
                // set it back to the session variable
                $this->wire('session')->set($fieldName, $session_value);
            } else {
                $this->wire('session')->set($fieldName, [[$logintype => $value]]);
            }
        }


    }
