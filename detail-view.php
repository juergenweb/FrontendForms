<?php
    declare(strict_types=1);

// set user language

    /*
     * Template for detail view which will be displayed inside the modal panel
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: details.php
     * Created: 18.07.2022
     */

    $out = '<!DOCTYPE html>
<html>
<head>';
    $css = $this->wire('config')->urls->modules . 'Markup/MarkupAdminDataTable/MarkupAdminDataTable.css';
    $out .= '<link rel="stylesheet" href="' . $css . '">';
    $js = $this->wire('config')->urls->modules . 'Markup/MarkupAdminDataTable/MarkupAdminDataTable.js';
    $out .= '<script type="text/javascript" src="' . $js . '"></script>';

    $out .= '<style>
        body#detail-view{
            padding:10px;
        }
        
        #linkwrapper {
            text-align: center;
            padding: 15px;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        table td, table th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table tr:hover {
            background-color: #ddd;
        }

        table th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
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
<body id="detail-view">';
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    $ip = basename($url);
    $ipdata = \ProcessWire\FrontendForms::getIPData($ip);
    $out .= '<h1>'.sprintf($this->_('More information about %s'), $ip).'</h1>';
    if ($ipdata->status == 'success') {
        $out .= '<h2>'.$this->_('Geo location').'</h2>';
        $out .= '<dl id="detail-view-geo">';
        $out .= '<dt>' . $this->_('Country') . '</dt><dd>' . $ipdata->country . '</dd>';
        $out .= '<dt>' . $this->_('Region') . '</dt><dd>' . $ipdata->regionName . '</dd>';
        $out .= '<dt>' . $this->_('City') . '</dt><dd>' . $ipdata->city . '</dd>';
        $out .= '</dl>';
    } else {
        $this->_('We are sorry, but ip-api.com does not have any information about this IP address.');
    }

    $out .= $this->executeWhoisLookUp($ip);
    $out .= $this->executeViewDetailsTable($ip);
    $out .= '</body>
</html>';
