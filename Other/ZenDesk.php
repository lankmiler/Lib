<?php

namespace Other;

use Zendesk\API\HttpClient as ZendeskAPI;
use Zendesk\API\Exceptions\ApiResponseException;
use Zendesk\API\Utilities\OAuth;

class ZenDesk {
	
	public static $desk;
    private static $_instance;
    private static $username = 'cl@incorporatenow.com';
    private static $subdomain = 'incorporatenow';
    private static $token;

    public static function getToken()
    {
        return self::$token;
    }

    private static function getSecret()
    {
        return '4f41b437b12053be1e639b191d6b50a7b2aca16c1e4b64d702f31ea4b6ff9652';
    }

    public static function instance($params = [])
    {
        if(empty(self::$_instance))
            self::$_instance = new self($params);
        return self::$_instance;
    }

    public static function getOauthData()
    {
        return [
            'subdomain' => self::$subdomain,
            'username' => self::$username,
            'client_secret' => self::getSecret(),
            'client_id' => 'incorporate'
        ];
    }

    public function comments()
    {
        return self::$desk->comments();
    }

    public function getTicketsList()
    {
        try {
            return self::$desk->tickets()->findAll();
        } catch (ApiResponseException $e) {
            echo $e->getMessage();
            die();
        }
    }

    private function loginDesk($params = [])
    {
        $state = base64_encode(serialize(self::getOauthData()));
        $oAuthUrl= OAuth::getAuthUrl(
            self::$subdomain,
            [
                'client_id' => 'incorporate',
                'state' => $state,
            ]
        );

        if(!array_key_exists('code', $_REQUEST)) {
            header('Location: ' . $oAuthUrl);
            exit(0);
        }

        $data = unserialize(base64_decode($_GET['state']));
        $data['code'] = $_REQUEST['code'] or die('Can\'t get code');

        $data['redirect_uri'] = 'https://' . $_SERVER['HTTP_HOST'] . '/checkout-success';
        
        if(array_key_exists('redirect_uri', $params)) {
            $data['redirect_uri'] = 'https://' . $_SERVER['HTTP_HOST'] . $params['redirect_uri'];
        }

        try {
            $response = OAuth::getAccessToken(new \GuzzleHttp\Client(), $data['subdomain'], $data);
            self::$desk = new ZendeskAPI($data['subdomain']);
            self::$desk->setAuth(\Zendesk\API\Utilities\Auth::OAUTH, ['token' => $response->access_token]);
        } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
            echo "<h1>Error!</h1>";
            echo "<p>We couldn't get an access token for you (ZenDesk::loginDesk). Please check your credentials and try again.</p>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    public function getComment($ticket_id)
    {
        return self::$desk->tickets()->comments()->findAll([
            'ticket_id' => $ticket_id
        ]);
    }

    public function makePrivateComment($ticket_id)
    {
        $comment = $this->getComment($ticket_id);
        return self::$desk->tickets()->comments()->makePrivate([
            'id' => $comment->comments[0]->id,
            'ticket_id' => $ticket_id
        ]);
    }


    public function createTicket($params)
    {
        if(!is_null(self::$desk)) {
            return self::$desk->tickets()->create($params);
        } else {
            return false;
        }
    }

    public function updateTicket($ticket_id, $params = array())
    {
        if(!is_null(self::$desk)) {
            return self::$desk->tickets()->update($ticket_id,['ticket' => [$params]]);
        } else {
            return false;
        }
    }

    private function __construct($params = [])
    {
        $this->loginDesk($params);
    }
}