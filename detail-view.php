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
    </style>

</head>
<body>';
$out .= $this->executeWhoisLookUp($event->arguments('ip'));
$out .= $this->executeViewDetailsTable($event->arguments('ip'));
$out .= '</body>
</html>';



