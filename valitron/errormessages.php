<?php

namespace ProcessWire;


/*
 * File containing array of all error messages for the default validators of Valitron
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: errormessages.php
 * Created: 24.02.2023 
 */


return [
    'required'       => __('is required.'),
    'equals'         => __('must be the same as %s.'),
    'different'      => __('must be different than %s.'),
    'accepted'       => __('must be accepted.'),
    'numeric'        => __('must be numeric.'),
    'integer'        => __('must be an integer.'),
    'length'         => __('must be %s characters long.'),
    'min'            => __('must be at least %s.'),
    'max'            => __('must be no more than %s.'),
    'listContains'   => __('contains invalid value.'),
    'in'             => __('contains invalid value.'),
    'notIn'          => __('contains invalid value.'),
    'ip'             => __('is not a valid IP address.'),
    'ipv4'           => __('is not a valid IPv4 address.'),
    'ipv6'           => __('is not a valid IPv6 address.'),
    'email'          => __('is not a valid email address.'),
    'url'            => __('is not a valid URL.'),
    'urlActive'      => __('must be an active domain.'),
    'alpha'          => __('must contain only letters a-z.'),
    'alphaNum'       => __('must contain only letters a-z and/or numbers 0-9.'),
    'slug'           => __('must contain only letters a-z, numbers 0-9, dashes and underscores.'),
    'regex'          => __('contains invalid characters.'),
    'date'           => __('is not a valid date.'),
    'dateFormat'     => __('must be date with format %s.'),
    'dateBefore'     => __('must be date before %s.'),
    'dateAfter'      => __('must be date after %s.'),
    'contains'       => __('must contain %s.'),
    'boolean'        => __('must be a boolean.'),
    'lengthBetween'  => __('must be between %s and %s characters.'),
    'creditCard'     => __('must be a valid credit card number.'),
    'lengthMin'      => __('must be at least %s characters long.'),
    'lengthMax'      => __('must not exceed %s characters.'),
    'instanceOf'     => __('must be an instance of %s.'),
    'containsUnique' => __('must contain unique elements only.'),
    'requiredWith'   => __('is required.'),
    'requiredWithout'=> __('is required.'),
    'subset'         => __('contains an item that is not in the list.'),
    'arrayHasKeys'   => __('does not contain all required keys.'),
];
