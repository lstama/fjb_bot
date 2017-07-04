<?php

require 'vendor/autoload.php';
include 'Sender.php';

class User_Session extends CI_Controller {

	public $session;
	public $oauth_client;
	public $request_token;
	public $access_token;
	public $status = 'trying_to_login';
	public $content;

	public function __construct($content) {

		parent::__construct();
		$this->content = $content;
		$this->oauth_client = new \Kaskus\KaskusClient($this->content['bot_account']->consumer_key, $this->content['bot_account']->consumer_secret, "https://webstaging.kaskus.co.id/api/oauth/");
		$this->oauth_client->setDefaultOption('verify', false);
		if ($content['redirect']) {
			
			return;
		}

		$this->checkSession();
	}

	public function checkSession() {

		$this->load->model('session_model');
		$this->session = $this->session_model->find_session($this->content['user']->username);
		
		if (empty($this->session)) {

    		$this->startSession();
    		return;
    	}

    	#TODO : HOW TO CHECK ACCESS TOKEN VALID OR NOT
		/*$status = 'logged_on';
		$last_session = $this->session['last_session'];

		if ($last_session == 'trying_to_login') {

			$last_session = 'logged_on';
		}

		$data = array(
			'token'          => $access_token->oauth_token,
			'token_secret'   => $access_token->oauth_token_secret,
			'last_session'   => $last_session
			);

		$this->session_model->update_session($this->content['user']->username, $data);*/
	}

	public function createSession() {
		
		$this->load->model('session_model');
        $data = array(
			'username'       => $this->content['user']->username,
			'JID'       	 => $this->content['user']->JID,
			'token'          => $this->request_token['oauth_token'],
			'token_secret'   => $this->request_token['oauth_token_secret'],
			'last_session'   => 'trying_to_login'
			);

		$this->session_model->create_session($data);

	}

	public function sendAuthorizeUrl($url) {

		$sender = new Sender();
		$b = array($sender->button($url, 'Authorize'));
		$i['interactive'] = $sender->interactive(null, null, 'Klik tombol di bawah untuk authorize FJB Bot', $b, null);
		$sender->sendReply($i);
	}   

	public function startSession() {

		#Create new session
		$this->load->model('session_model');
		$this->request_token = $this->oauth_client->getRequestToken($this->content['bot_account']->bot_callback_url);
		$this->createSession();
		$this->session = $this->session_model->find_session($this->content['user']->username);
	
		#Check if bot has access to user account
    	$this->oauth_client->setCredentials($this->session['token'], $this->session['token_secret']);
		$authorize_url = $this->oauth_client->getAuthorizeUrl($this->session['token']);
			
		#Send authorize url to user
		$this->sendAuthorizeUrl($authorize_url);
		return;
	}

	public function authorizeSession() {

		$this->load->model('session_model');
		$this->session = $this->session_model->find_token($this->content['user']->username);
		$this->oauth_client->setCredentials($this->content['message'], $this->session['token_secret']);
		$this->access_token = $this->oauth_client->getAccessToken();

		if ($this->access_token['access'] === 'GRANTED') {

			$status = 'logged_on';
			$last_session = $this->session['last_session'];

			if ($last_session == 'trying_to_login') {

				$last_session = 'logged_on';
			}

			$data = array(
				'token'          => $this->$access_token['oauth_token'],
				'token_secret'   => $this->$access_token['oauth_token_secret'],
				'last_session'   => $last_session,
				'userid' 		 => $this->$access_token['userid']
				);

			$this->session_model->update_session($this->$access_token['username'], $data);
		}

		#send menu utama 
	}
}