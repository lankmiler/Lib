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
        return '616dec645a727a64b77dd9564b9f301833913b48c489e89baf092f49165f2e5c';
    }

    public static function instance()
    {
        if(empty(self::$_instance))
            self::$_instance = new self();
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

    private function loginDesk()
    {
        $state = base64_encode(serialize(self::getOauthData()));
        $oAuthUrl= OAuth::getAuthUrl(
            self::$subdomain,
            [
                'client_id' => 'incorporate',
                'state' => $state,
            ]
        );

        if(empty($_REQUEST['code']))
            header('Location: '. $oAuthUrl);
   

        $params = unserialize(base64_decode($_GET['state']));
        $params['code'] = $_REQUEST['code'] or die('Can\'t get code');
        $params['redirect_uri'] = 'https://' . $_SERVER['HTTP_HOST'] . '/checkout-success';

        try {
            $response = OAuth::getAccessToken(new \GuzzleHttp\Client(), $params['subdomain'], $params);
            self::$desk = new ZendeskAPI($params['subdomain']);
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
        $this->loginDesk();
    }
}