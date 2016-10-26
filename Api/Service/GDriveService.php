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

    private $credentials_path; //__DIR__.'/../../htdocs/drive-php-quickstart.json';

    private $client_secret_path; //__DIR__ . '/../../htdocs/client_secrets.json';

    protected $scopes;

    public $storage;

    private $redirect_uri;


    public function _construct(string $credentials_path, string $client_secret_path, string $redirect_uri, Session $storage)
    {
    	$this->credentials_path = $credentials_path;
    	$this->client_secret_path = $client_secret_path;
    	$this->storage = $storage;
    	$this->redirect_uri = $redirect_uri;

    	$client = new \Google_Client();
        $client->setApplicationName($this->application_name);
        $client->setAccessType('offline');
        $client->setAuthConfig($this->client_secret_path);
    	$client->setRedirectUri($redirect_uri);

        return $client;
    }

    public function tryLogin()
    {
        if($this->storage->get('gdrive_a_token')) {
            return $this->client->setAccessToken($this->storage->get('gdrive_a_token'));
        }
        //$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/gdrive-oauth';
        header('Location: ' . filter_var($this->redirect_uri,  FILTER_SANITIZE_URL));
        return false;
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
            echo '<pre>';
            print $e->getMessage();
            exit();
        }
    }

    public function uploadFile($toUpload)
    {
        $this->tryLogin();
        $file = new \Google_Service_Drive_DriveFile();
        $file->setName(uniqid().'.jpg');
        $file->setDescription('A test document');
        $file->setMimeType('image/jpeg');
        $drive_service = new \Google_Service_Drive($this->client);

        try {
            return $drive_service->files->create($file, array(
                'data' => file_get_contents($toUpload),
                'mimeType' => 'application/octet-stream',
                'uploadType' => 'media'
            ));
        } catch (\Google_Service_Exception $e) {
            echo '<pre>';
            print $e->getMessage();
            exit();
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

    public function login($request)
    {
        // if(!isset($request->get('code'))) {
        //     $auth_url = $this->client->createAuthUrl();
        //     header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        // } else {
        //     $this->client->authenticate($request->get('code'));
        //     save_var('access_token', $this->client->getAccessToken());
        //     $redirect_uri = 'http://'. $_SERVER['HTTP_HOST'] .'/gdrive';
        //     header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        // }
    }

    public function getFoldersList()
    {
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
        } catch (Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }

    public function createFolder($name)
    {
        //$this->tryLogin();

        $fileMetadata = new \Google_Service_Drive_DriveFile(array(
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder'));

        $drive_service = new \Google_Service_Drive($this->client);

        $file = $drive_service->files->create($fileMetadata, array(
            'fields' => 'id'));
        printf("Folder ID: %s\n", $file->id);
    }
}