<?php

require_once 'vendor/autoload.php';

use Api\Service\GDriveService as gDrive;

$gdrive = new gDrive();

var_dump($gdrive);