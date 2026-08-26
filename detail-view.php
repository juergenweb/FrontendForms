<?php

declare(strict_types=1);

use function ProcessWire\__;

/*
 * Template for detail view which will be displayed inside the modal panel
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: detail-view.php
 * Created: 18.07.2022
 */

$url = strtok($_SERVER['REQUEST_URI'], '?');
$ip = basename($url);

// validate that the last URL segment is actually a valid IP address
// before using it anywhere (prevents XSS and bogus API lookups)
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    $out = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body id="detail-view" class="pw">';
    $out .= '<p>' . htmlspecialchars(__('Invalid IP address.', __FILE__), ENT_QUOTES, 'UTF-8') . '</p>';
    $out .= '</body>
</html>';
    return;
}

$out = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">';

// load the current admin theme's own main stylesheet, so this panel
// visually matches the rest of the admin.
//
// NOTE: wire('adminTheme') is only populated by ProcessWire's own
// AdminTheme::init() bootstrap, which explicitly skips itself unless the
// current page's template is 'admin' - this custom /detail-view/{ip}
// URL-hook route never has that template, so wire('adminTheme') is
// never actually set here. isUikitAdminThemeActive() reads the user's
// own admin_theme profile field directly (falling back to the site-wide
// default) instead, which works regardless of that bootstrap logic
// having run.
$isUikitBased = $this->isUikitAdminThemeActive();

if ($isUikitBased) {
    $files = $this->wire('files');
    $themeClass = $this->getActiveAdminThemeClass();
    $themeModulePath = $this->wire('config')->paths->{$themeClass};
    $themeModuleUrl = $this->wire('config')->urls->{$themeClass};
    $uikitThemeName = $this->wire('config')->AdminThemeUikit['style'] ?: 'default';

    // Mirrors the actual <link> order the real admin (AdminThemeUikit)
    // outputs, since none of this gets generated automatically outside
    // of an admin-template page render (see the note above):
    // 1. Uikit's own base framework CSS
    // 2. shared AdminTheme.css (used by every core admin theme)
    // 3. admin-custom.css, then admin.css - the theme's own CSS, in
    //    that order. admin.css can also live in the runtime-compiled
    //    site/assets/admin.css instead, if custom LESS styling is
    //    actively configured for this install - that variant is tried
    //    first for that one file, falling back to the module-bundled
    //    default otherwise.
    $candidates = [
        $themeModulePath . 'uikit-pw/pw.min.css' => $themeModuleUrl . 'uikit-pw/pw.min.css',
        $this->wire('config')->paths->adminTemplates . 'styles/AdminTheme.css'
        => $this->wire('config')->urls->adminTemplates . 'styles/AdminTheme.css',
        $themeModulePath . 'themes/' . $uikitThemeName . '/admin-custom.css'
        => $themeModuleUrl . 'themes/' . $uikitThemeName . '/admin-custom.css',
    ];

    foreach ($candidates as $path => $url) {
        if ($files->exists($path)) {
            $out .= '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
        }
    }

    // admin.css itself: prefer the runtime-compiled site/assets/admin.css
    // (reflects any customization) over the module-bundled default.
    $adminCssPath = $this->wire('config')->paths->assets . 'admin.css';
    $adminCssUrl = $this->wire('config')->urls->assets . 'admin.css';

    if (!$files->exists($adminCssPath)) {
        $adminCssPath = $themeModulePath . 'themes/' . $uikitThemeName . '/admin.css';
        $adminCssUrl = $themeModuleUrl . 'themes/' . $uikitThemeName . '/admin.css';
    }

    if ($files->exists($adminCssPath)) {
        $out .= '<link rel="stylesheet" href="' . htmlspecialchars($adminCssUrl, ENT_QUOTES, 'UTF-8') . '">';
    }
} elseif ($this->getActiveAdminThemeClass() === 'AdminThemeDefault') {
    $files = $this->wire('files');
    $themeClass = 'AdminThemeDefault';
    $themeModulePath = $this->wire('config')->paths->{$themeClass};
    $themeModuleUrl = $this->wire('config')->urls->{$themeClass};

    $candidates = [
        $themeModulePath . 'styles/main-classic.css' => $themeModuleUrl . 'styles/main-classic.css',
        $this->wire('config')->paths->adminTemplates . 'styles/AdminTheme.css'
        => $this->wire('config')->urls->adminTemplates . 'styles/AdminTheme.css',
    ];

    foreach ($candidates as $path => $url) {
        if ($files->exists($path)) {
            $out .= '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
        }
    }
}

$css = $this->wire('config')->urls->modules . 'Markup/MarkupAdminDataTable/MarkupAdminDataTable.css';
$out .= '<link rel="stylesheet" href="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">';

$js = $this->wire('config')->urls->modules . 'Markup/MarkupAdminDataTable/MarkupAdminDataTable.js';
$out .= '<script type="text/javascript" src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '"></script>';

$out .= '<style>

        .uk-table td {
  padding:16px 12px!important;
}
        body#detail-view{
            padding:10px;
        }

        #linkwrapper {
            text-align: center;
            padding: 15px;
        }

        dl {
    border: 3px double #ccc;
    padding: 0.5em;
  }
  dt {
    float: left;
    clear: left;
    width: 100px;
    text-align: right;
    font-weight: bold;
    color: green;
  }
  dt::after {
    content: ":";
  }
  dd {
    margin: 0 0 0 110px;
    padding: 0 0 0.5em 0;
  }
    </style>

</head>
<body id="detail-view" class="pw">';

$ipdata = \ProcessWire\FrontendForms::getIPData($ip);

$out .= '<h1>' . sprintf(htmlspecialchars(__('More information about %s', __FILE__), ENT_QUOTES, 'UTF-8'), htmlspecialchars($ip, ENT_QUOTES, 'UTF-8')) . '</h1>';

if (is_object($ipdata) && $ipdata->status == 'success') {
    $out .= '<h2>' . htmlspecialchars(__('Geo location', __FILE__), ENT_QUOTES, 'UTF-8') . '</h2>';
    $out .= '<dl id="detail-view-geo">';
    $out .= '<dt>' . htmlspecialchars(__('Country', __FILE__), ENT_QUOTES, 'UTF-8') . '</dt><dd>' . htmlspecialchars((string) $ipdata->country, ENT_QUOTES, 'UTF-8') . '</dd>';
    $out .= '<dt>' . htmlspecialchars(__('Region', __FILE__), ENT_QUOTES, 'UTF-8') . '</dt><dd>' . htmlspecialchars((string) $ipdata->regionName, ENT_QUOTES, 'UTF-8') . '</dd>';
    $out .= '<dt>' . htmlspecialchars(__('City', __FILE__), ENT_QUOTES, 'UTF-8') . '</dt><dd>' . htmlspecialchars((string) $ipdata->city, ENT_QUOTES, 'UTF-8') . '</dd>';
    $out .= '</dl>';
} else {
    $out .= '<p>' . htmlspecialchars(__('We are sorry, but ip-api.com does not have any information about this IP address.', __FILE__), ENT_QUOTES, 'UTF-8') . '</p>';
}

$out .= $this->executeWhoisLookUp($ip);
$out .= $this->executeViewDetailsTable($ip);
$out .= '</body>
</html>';
