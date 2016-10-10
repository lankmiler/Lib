<?php

namespace Api;

use \Curl\Curl;

class Caller {

	private $_token;
	private $api_url;
	private $storage;
	private $config;

	public function setConfig($data)
	{
		$this->config = $data;
	}

	private function accessTokenCall($access_token_url)
	{
		$curl = new Curl($this->config);
		return $curl->post($access_token_url, );
	}

	public function setApiUrl($url)
	{
		$this->api_url = $url;
	}

	private function checkToken()
	{
		$access_token_url = $this->api_url.'oauth/access_token';
		$response = $this->accessTokenCall($access_token_url);

		if(!empty($response) && is_object($response)) {
			
			$_SESSION[PROJECT_NAMESPACE]['api_token'] = [
				'access_token' => $response->access_token,
				'token_type' => $response->token_type,
				'expires_in' => $response->expires_in
			];

			return $this->setToken(
				$response->access_token, 
				$response->token_type, 
				$response->expires_in
			);
		}

		return false;
	}

	public function beforeCall()
	{
		if(!isset($this->_token)) {
			$this->checkToken();
		} else if($this->_token->isExpired()) {
			$this->checkToken();
		}

		return $this;
	}

	public function getToken()
	{
		return $this->_token;
	}

	public function setToken($access_token, $token_type, $expires_in)
	{
		$this->_token = new Token($access_token, $token_type, $expires_in);
		return $this->_token;
	}


	public function postCall($url, $data = [])
	{
		$this->beforeCall();
		$curl = new Curl();

		if(!is_null($this->_token)) 
			$data['access_token'] = $this->_token->getAccessToken();

		// @TODO fix this one
		$result = $curl->post($this->api_url.'api'.$url, $data);

		if(!is_array($result) && !is_object($result)) {
			return [];
		}

		return $result;
	}

	public function getCall($url, $data = [])
	{
		$this->beforeCall();
		$curl = new Curl();

		if(!is_null($this->_token)) 
			$data['access_token'] = $this->_token->getAccessToken();

		// @TODO fix this one
		$result = $curl->get($this->api_url.'api'.$url, $data);

		if(!is_array($result) && !is_object($result)) {
			return [];
		}

		return $result;
	}

	public function getStorage()
	{
		return $this->storage;
	}

	public function setStorage()
	{
		$this->storage = &$_SESSION;
		return $this;
	}

}