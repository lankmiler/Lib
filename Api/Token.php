<?php

namespace Lib\Api;

class Token {

	private $access_token;

	public $token_type;

	public $expires_in;

	public $created;

	public function __construct($access_token, $token_type, $expires_in)
	{
		$this->access_token = $access_token;
		$this->token_type = $token_type;
		$this->expires_in = $expires_in;
		$this->created = date('Y-m-d h:i:s');
	}

	public function getTokenType()
	{
		return $this->token_type;
	}

	public function isExpired()
	{
		return $this->timeLeft() < 0;
	}

	public function timeLeft()
	{
		$date1 = new \DateTime();
		$date1->setTimestamp(strtotime($this->created) + $this->expires_in);
		$date2 = new \DateTime();
		$date2->setTimestamp(time());
		return  $date1->getTimestamp() - $date2->getTimestamp();
	}

	public function setAccessToken($token)
	{
		$this->access_token = $token;
		return $token;
	}

	public function getAccessToken()
	{
		return $this->access_token;
	}


}