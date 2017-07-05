<?php

require 'vendor/autoload.php';
include 'Sender.php';

class User_Session extends CI_Controller {

	public $session;
	public $oauth_client;
	public $request_token;
	public $access_token;
	public $status = 'trying_to_login';
	public $logged_on_user = 'Anon';
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
    	try {

			
    		$this->oauth_client->setCredentials($this->session['token'], $this->session['token_secret']);
			$response = $this->oauth_client->get('user');
			$temp = $response->json();
    	}
    	catch (\Kaskus\Exceptions\KaskusRequestException $exception) {
 	  		// Kaskus Api returned an error
    		$response =  $exception->getMessage();
		} 
		catch (\Exception $exception) {
    		// some other error occured
    		$response =  $exception->getMessage();
		}

		if ( (gettype($response) != 'string') and (isset($temp)) ) {

			$this->status = 'logged_on';
			$this->logged_on_user = $this->session['user'];
			return;
		}

		else {

			#Send authorize url to user
    		$this->oauth_client->setCredentials($this->session['token'], $this->session['token_secret']);
			$authorize_url = $this->oauth_client->getAuthorizeUrl($this->session['token']);
		
			$this->sendAuthorizeUrl($authorize_url);
			return;	
		}

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

	public function sendAuthorizeUrl($url, $not_reply = FALSE) {

		$sender = new Sender();
		$b = array($sender->button($url, 'Authorize'));
		$i['interactive'] = $sender->interactive(null, "Anda belum login", "Klik tombol di bawah untuk authorize FJB Bot.", $b, null);

		if ($not_reply) {

			$i['interactive']['caption'] = "Pastikan anda sudah logout dari akun yang sebelumnya.\nKlik tombol di bawah untuk authorize FJB Bot.";
			$sender->sendMessage($this->content['bot_account'], $this->content['user'], $i);
			return;
		}

		$sender->sendReply($i);
	}   

	public function startSession($error_on_auth = FALSE) {

		#Create new session
		$this->load->model('session_model');

		if ($error_on_auth) {

			$this->oauth_client = new \Kaskus\KaskusClient($this->content['bot_account']->consumer_key, $this->content['bot_account']->consumer_secret, "https://webstaging.kaskus.co.id/api/oauth/");
			$this->oauth_client->setDefaultOption('verify', false);	
		}
		
		$this->request_token = $this->oauth_client->getRequestToken($this->content['bot_account']->bot_callback_url);

		$this->createSession();
		$this->session = $this->session_model->find_session($this->content['user']->username);
	
		#Send authorize url to user
    	$this->oauth_client->setCredentials($this->session['token'], $this->session['token_secret']);
		$authorize_url = $this->oauth_client->getAuthorizeUrl($this->session['token']);
		
		$this->sendAuthorizeUrl($authorize_url, $error_on_auth);
		return;
	}

	public function authorizeSession() {

		$this->load->model('session_model');
		$this->session = $this->session_model->find_token($this->content['user']->username);
		$this->oauth_client->setCredentials($this->content['message'], $this->session['token_secret']);
		$this->access_token = $this->oauth_client->getAccessToken();

		if ($this->access_token['access'] === 'GRANTED') {

			#username tidak sama dengan username login
			if ($this->session['username'] != $this->access_token['username']) {


				$status = 'trying_to_login';
				$last_session = $this->session['last_session'];
				$this->content['user']->username = $this->session['username'];
				$this->content['user']->JID = $this->session['JID'];

				$this->session_model->delete_session($this->session['username']);

				$this->startSession(TRUE);

				echo "Authorisasi gagal, akun Kaskus Chat dan akun authorisasi berbeda. Silakan logout terlebih dahulu dari akun authorisasi yang sekarang (dari browser). Kami telah mengirimkan link authorisasi yang baru, silakan buka Kaskus Chat lagi.";
				return;
			}

			$status = 'logged_on';
			$last_session = $this->session['last_session'];

			if ($last_session == 'trying_to_login') {

				$last_session = 'logged_on';
			}

			$data = array(
				'token'          => $this->access_token['oauth_token'],
				'token_secret'   => $this->access_token['oauth_token_secret'],
				'last_session'   => $last_session,
				'userid' 		 => $this->access_token['userid'],
				'user' 		 	 => $this->access_token['username']
				);

			// $this->session_model->update_session($this->access_token['username'], $data);
			$this->session_model->update_session($this->session['username'], $data);

			echo "Authorisasi berhasil. Silakan kembali ke apps untuk mulai melanjutkan.";
			return;

		}

		if ($this->access_token['access'] === 'DENIED') {

			$status = 'trying_to_login';
			$last_session = $this->session['last_session'];
			
			$this->content['user']->username = $this->session['username'];
			$this->content['user']->JID = $this->session['JID'];
			$this->session_model->delete_session($this->session['username']);
			
			$this->startSession(TRUE);

			echo "Authorisasi gagal. Silakan kembali ke apps untuk mendapatkan link authorisasi yang baru.";
			return;
		}
		#send menu utama 
	}
}