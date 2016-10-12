<?php

namespace Other;

use Zendesk\API\HttpClient as ZendeskAPI;
use Zendesk\API\Exceptions\ApiResponseException;
use Zendesk\API\Utilities\OAuth;

class ZenDesk {
	
	public $desk;
    private $token;
    private $config;
    // private static $_instance;
    // private static $username = 'cl@incorporatenow.com';
    // private static $subdomain = 'incorporatenow';

    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setToken($token)
    {
        $this->config['token'] = $token;
        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function __construct($config)
    {
        $this->setConfig($config);
        return $this;
        //$this->loginDesk($params);
    }

    private function getSecret()
    {
        return $this->config['client_secret'];
    }

    public function comments()
    {
        return $this->desk->comments();
    }

    public function getTicketsList()
    {
        try {
            return $this->desk->tickets()->findAll();
        } catch (ApiResponseException $e) {
            echo $e->getMessage();
            die();
        }
    }

    private function loginDesk($params = [])
    {
        $this->desk = new ZendeskAPI($this->config['subdomain']);
        $this->desk->setAuth('basic', [
            'username' => $this->config['username'],
            'token' => $this->config['token']
        ]);

        return $this;
        // $state = base64_encode(serialize([
        //     'subdomain'
        // ]));
        // $oAuthUrl= OAuth::getAuthUrl(
        //     self::$subdomain,
        //     [
        //         'client_id' => 'incorporate',
        //         'state' => $state,
        //     ]
        // );

        // if(!array_key_exists('code', $_REQUEST)) {
        //     header('Location: ' . $oAuthUrl);
        //     exit(0);
        // }

        // $data = unserialize(base64_decode($_GET['state']));
        // $data['code'] = $_REQUEST['code'] or die('Can\'t get code');

        // $data['redirect_uri'] = 'https://' . $_SERVER['HTTP_HOST'] . '/checkout-success';
        
        // if(array_key_exists('redirect_uri', $params)) {
        //     $data['redirect_uri'] = 'https://' . $_SERVER['HTTP_HOST'] . $params['redirect_uri'];
        // }

        // try {
        //     $response = OAuth::getAccessToken(new \GuzzleHttp\Client(), $data['subdomain'], $data);
        //     self::$desk = new ZendeskAPI($data['subdomain']);
        //     self::$desk->setAuth(\Zendesk\API\Utilities\Auth::OAUTH, ['token' => $response->access_token]);
        // } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
        //     echo "<h1>Error!</h1>";
        //     echo "<p>We couldn't get an access token for you (ZenDesk::loginDesk). Please check your credentials and try again.</p>";
        //     echo "<p>" . $e->getMessage() . "</p>";
        // }
    }

    public function getComment($ticket_id)
    {
        return $this->desk->tickets()->comments()->findAll([
            'ticket_id' => $ticket_id
        ]);
    }

    public function makePrivateComment($ticket_id)
    {
        $comment = $this->getComment($ticket_id);

        return $this->desk->tickets()->comments()->makePrivate([
            'id' => $comment->comments[0]->id,
            'ticket_id' => $ticket_id
        ]);
    }


    public function createTicket($params)
    {
        if(!is_null($this->desk)) {
            return $this->desk->tickets()->create($params);
        } else {
            return false;
        }
    }

    public function updateTicket($ticket_id, $params = array())
    {
        if(!is_null($this->desk)) {
            return $this->desk->tickets()->update($ticket_id,['ticket' => [$params]]);
        } else {
            return false;
        }
    }
}