<?php
declare(strict_types=1);

/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: captchaimage.php
 * Created: 01.10.2022
 */

use FrontendForms\AbstractCaptchaFactory;

include("index.php");
$query = $input->queryString();
$parameter = parse_str($query, $output);
$session->set('query', $query);
$captchaType = $output['cat'];
$captchaVariant = $output['type'];
$formID = $output['formID'];

header('Content-Type: image/png');
header("Expires: Tue, 01 Jan 2013 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// create an object of the appropriate CAPTCHA class defined by $captchaType and $captchaVariant
$captcha = AbstractCaptchaFactory::make($captchaType, $captchaVariant);
$captcha->createCaptchaImage($formID);

