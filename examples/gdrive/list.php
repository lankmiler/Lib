<?php

require_once dirname(dirname(dirname(__FILE__))).'/Api/Service/GDriveService.php';
require_once dirname(dirname(dirname(__FILE__))).'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Session\Session;
use Api\Service\GDriveService;

$session = new Session();
$session->start();
$client_secret_path  = './config/client_secret.json';
$credentials_path = './config/drive-php-quickstart.json';

//session->clear();

$gdrive = new GDriveService($credentials_path,
       $client_secret_path,
       'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'],
       $session);

//$gdrive->tryLogin();

//$list = $gdrive->getFoldersList();
//$result = $gdrive->moveFile('0B5FkT0oxsaK7Yk12YmNRY3kwMUU', '0B5FkT0oxsaK7X0NPTUJSY0FrSVE');

// var_dump($result);
// die();