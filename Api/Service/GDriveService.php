<?php

namespace Api\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;

class GDriveService {

	private $client;

    private $application_name = 'gdrive';

    private $credentials_path;

    private $client_secret_path; 

    protected $scopes;

    public $storage;

    private $redirect_uri;


    public function __construct($credentials_path, $client_secret_path, $redirect_uri,  $storage)
    {
    	$this->credentials_path = $credentials_path;
    	$this->client_secret_path = $client_secret_path;

        if($storage instanceof \Symfony\Component\HttpFoundation\Session\Session or $storage instanceof \Illuminate\Session\Store) {
    	   $this->storage = $storage;
        } else {
            throw new \Exception('You can use only Symfony or Laravel session classes');
        }

    	$this->redirect_uri = $redirect_uri;

    	$client = new \Google_Client();
        $client->setApplicationName($this->application_name);
        $client->setAccessType('offline');

        $client->setScopes(implode(' ', array(
            \Google_Service_Drive::DRIVE_METADATA_READONLY,
            \Google_Service_Drive::DRIVE_FILE,
            \Google_Service_Drive::DRIVE,
            )
        ));
        $client->setAuthConfig($this->client_secret_path);
    	$client->setRedirectUri($redirect_uri);

        $this->client = $client;

        return $client;
    }

    public function tryLogin()
    {
        if(is_object($this->storage)) {
            if($this->storage->get('access_token')) {
                $this->client->setAccessToken($this->storage->get('access_token'));
            }
        }

        if($this->storage->has('access_token') === true && !is_null($this->storage->get('access_token'))) {
            if($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $this->client->setAccessToken(json_encode($this->client->getAccessToken()));
                return true;
            } else {
                return true;
            }
        }

        if(!isset($_GET['code'])) {
            $auth_url = $this->client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        } else {
            $result = $this->client->authenticate($_GET['code']);
            $this->storage->set('access_token', $this->client->getAccessToken());
        }
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    public function setScopes()
    {
        $this->client->addScope(\Google_Service_Drive::DRIVE);
        $this->client->addScope(\Google_Service_Drive::DRIVE_FILE);
    }

    public function getFilesFromDirectory()
    {
        return true;
    }

    public function downloadFile($fileId)
    {
        $this->tryLogin();

        if(empty($fileId)) {
            throw new Exception('Empty fileid for Servide GoogleDrive:downloadFile');
        }

        $service = new \Google_Service_Drive($this->client);

        try {
            $file = $service->files->get($fileId, array(
                'alt' => 'media'
            ));

            foreach ($file->getHeaders() as $name => $values) {
                header($name . ': ' . implode(', ', $values));
            }
            header('Content-Disposition: inline; filename="' . $fileId . '"');
            echo $file->getBody();
        } catch(\Google_Service_Exception $e) {
            $this->parseError($e);
        }
    }

    public function uploadFile($uploadFile)
    {
        $this->tryLogin();

        try { 
            $service = new \Google_Service_Drive($this->client);
            $file = new \Google_Service_Drive_DriveFile();

            $fileMetadata = new \Google_Service_Drive_DriveFile(array(
                  'name' => basename($uploadFile),
            ));

            return $service->files->create($fileMetadata, array(
               'data' => file_get_contents($uploadFile),
               'mimeType' => mime_content_type($uploadFile),
               'uploadType' => 'multipart',
               'fields' => 'id'
            ));
        } catch (\Google_Service_Exception $e) {
            $this->parseError($e);
        }
    }

    public function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

    public function getFoldersList()
    {
        $this->tryLogin();
        $drive_service = new \Google_Service_Drive($this->client);
        return $drive_service->files->listFiles(array())->getFiles();
    }

    public function setClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName($this->application_name);
        $client->setAccessType('offline');
        $client->setAuthConfig($this->client_secret_path);
        $client->setRedirectUri('http://'. $_SERVER['HTTP_HOST'] .'/gdrive-oauth');

        return $client;
    }

    public function moveFile($fileId, $newParentId) {

        $this->tryLogin();

        $service = new \Google_Service_Drive($this->client);
        try {
            $emptyFileMetadata = new \Google_Service_Drive_DriveFile();
            // Retrieve the existing parents to remove
            $file = $service->files->get($fileId, array('fields' => 'parents'));
            $previousParents = join(',', $file->parents);
            // Move the file to the new folder
            $file = $service->files->update($fileId, $emptyFileMetadata, array(
                'addParents' => $newParentId,
                'removeParents' => $previousParents,
                'fields' => 'id, parents'));

            return $file;
        } catch (\Google_Service_Exception $e) {
            $this->parseError($e);
        }
    }

    public function createFolder($name)
    {
        try {
            $this->tryLogin();

            $fileMetadata = new \Google_Service_Drive_DriveFile(array(
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder'));

            $drive_service = new \Google_Service_Drive($this->client);

            $file = $drive_service->files->create($fileMetadata, array(
                'fields' => 'id'));
            printf("Folder ID: %s\n", $file->id);
        } catch(\Google_Service_Exception $e) {
            $this->parseError($e);
        }
    }

    public function parseError($e)
    {
        echo '<h2>Error !</h2><br />';
        $result = json_decode($e->getMessage());
        echo '<pre>';
        print_r($result->error->errors[0]);
        exit();
    }
}