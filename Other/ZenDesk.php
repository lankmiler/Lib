<?php

namespace Other;

use Zendesk\API\HttpClient as ZendeskAPI;
use Zendesk\API\Exceptions\ApiResponseException;
use Zendesk\API\Utilities\OAuth;

class ZenDesk {
	
	public $desk;
    private $token;
    private $config;
    private $default_fields = [];

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

    public function setDefaultCustomFields($values)
    {
        $this->default_fields = $values;
    }

    public function __construct($config)
    {
        return $this->setConfig($config);
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
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function loginDesk($params = [])
    {
        $this->desk = new ZendeskAPI($this->config['subdomain']);
        $this->desk->setAuth('basic', [
            'username' => $this->config['username'],
            'token' => $this->config['token']
        ]);

        return $this;
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
            $params['custom_fields'] = array(
                array(
                    'id' => '44975608',
                    'value' => $_SERVER['REMOTE_ADDR']
                )
            );
            return $this->desk->tickets()->create($params);
        } else {
            throw new \Exception('zend desk instance is empty. Please initiate it.');
        }
    }

    public function updateTicket($ticket_id, $params = array())
    {
        if(!is_null($this->desk)) {
            return $this->desk->tickets()->update($ticket_id,$params);
        } else {
            throw new \Exception('zend desk instance is empty. Please initiate it.');
        }
    }
}