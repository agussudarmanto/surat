<?php
/**
 * Aplikasi untuk konsep one file
 * Agus Sudarmanto, S.Kom.
 * 2014 Jun 27
 */
include "apps.php";
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
define("DB_SERVER"  , "localhost");
define("DB_USER"    , "surat");
define("DB_PASSWORD", "surat");
define("DB_NAME"    , "surat");
define("BASEPATH"   , "http://localhost/surat/");
define("APPS_PARAM_IDX"     , 2);
define("MODULE_PARAM_IDX"   , 3);
define("ACTION_PARAM_IDX"   , 4);
$a = new apps();