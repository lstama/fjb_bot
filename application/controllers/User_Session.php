<?php
require 'vendor/autoload.php';
class User_Session extends CI_Controller {

	public $session ;
	public $oauth_client = new \Kaskus\KaskusClient($content['bot_account']->consumer_key, $content['bot_account']->consumer_secret);
	public $request_token;
	public $access_token;
	public $status = 'trying_to_login';
	public $content;

	$this->load->model('session_model');

	public function __construct($content) {

		$this->content = $content;
		checkSession();
	}

	public function checkSession() {

		
		$session = $this->session_model->find_session($content['user']->username);
		
		if (empty($session)) {

    		#Create new session
    		$request_token = $client->getRequestToken($content['bot_account']->bot_callback_url);
			createSession();
    	}

    	$session = $this->session_model->find_session($content['user']->username);
    	
    	#Check if bot has access to user account
		$access_token = $client->getAccessToken();
		if ($access_token['access'] === 'DENIED') {

			$client->setCredentials($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
			$authorize_url = $client->getAuthorizeUrl($requestToken['oauth_token']);
			#Send authorize url to user
		} else {

			$status = 'logged_on';
			$last_session = $session['last_session'];

			if ($last_session == 'trying_to_login') {

				$last_session = 'logged_on';
			}

			$data = array(
				'token'          => $access_token->oauth_token,
				'token_secret'   => $access_token->oauth_token_secret,
				'last_session'   => $last_session;
				);

				$this->update_session($content['user']->username, $data);
		}
	}

	function createSession() {
		
        $data = array(
			'username'       => $content['user']->username,
			'token'          => $request_token->oauth_token,
			'token_secret'   => $request_token->oauth_token_secret,
			'last_session'   => 'trying_to_login'
			);

		$this->session_model->create_session($data);

	}   
}