<?php

require 'vendor/autoload.php';
include_once 'Sender.php';

class Session extends Sender {

	public $status;
	public $username;
	public $message;

	/** @var \Kaskus\KaskusClient $kaskus_client */
	public $kaskus_client;
	protected $last_session;

	protected $session_from_database;
	protected $request_token;
	protected $access_token;

	public function __construct() {

		parent::__construct();
		$this->status = 'trying_to_login';
		$this->initiateKaskusClient();
	}

	protected function initiateKaskusClient() {

		$this->kaskus_client = new \Kaskus\KaskusClient($this->consumer_key, $this->consumer_secret, $this->kaskus_api);
		#TODO : Delete this when in production.
		$this->kaskus_client->setDefaultOption('verify', false);
	}

	public function isLoggedOn() {

		$this->session_from_database = $this->session_model->find_session($this->username);

		if (empty($this->session_from_database)) {

			$this->startSession();
			return false;
		}

		if ($this->isAuthorized()) {

			$this->status = 'logged_on';
			$this->last_session = $this->session_from_database['last_session'];
			return true;
		} else {

			$this->sendAuthorizeUrl();
			return false;
		}
	}

	protected function startSession($error_on_authorization = FALSE) {

		if ($error_on_authorization) {

			#Reinitiate
			$this->initiateKaskusClient();
		}

		$this->createSessionInDatabase();
		$this->sendAuthorizeUrl($error_on_authorization);
	}

	protected function createSessionInDatabase() {

		$this->getRequestToken();

		$data = [
			'username' => $this->username,
			'JID' => $this->JID,
			'token' => $this->request_token['oauth_token'],
			'token_secret' => $this->request_token['oauth_token_secret'],
			'last_session' => 'trying_to_login'
		];

		$this->session_model->create_session($data);
		$this->session_from_database = $this->session_model->find_session($this->username);
	}

	protected function getRequestToken() {

		$this->request_token = $this->kaskus_client->getRequestToken($this->callback_url);
	}

	protected function sendAuthorizeUrl($error_on_authorization = FALSE) {

		$this->kaskus_client->setCredentials($this->session_from_database['token'], $this->session_from_database['token_secret']);

		$authorize_url = $this->kaskus_client->getAuthorizeUrl($this->session_from_database['token']);
		$buttons = [$this->createButton($authorize_url, 'Authorize')];
		$title = "Anda belum login";
		$caption = "Klik tombol di bawah untuk authorize FJB Bot.";
		$interactive = $this->createInteractive(null, $title, $caption, $buttons, null);

		if ($error_on_authorization) {

			$interactive['caption'] = "Pastikan anda sudah logout dari akun yang sebelumnya.
							\nKlik tombol di bawah untuk authorize FJB Bot.";
		}

		$this->sendInteractiveMessage($interactive);
	}

	#Call API to check user status;
	protected function isAuthorized() {

		try {

			$this->kaskus_client->setCredentials($this->session_from_database['token'], $this->session_from_database['token_secret']);
			$response = $this->kaskus_client->get('user');
			return true;
		} catch (\Kaskus\Exceptions\KaskusRequestException $exception) {

			return false;
		} catch (\Exception $exception) {

			return false;
		}
	}

	public function setLastSession($last_session) {

		$this->last_session = $last_session;
		$data = ['last_session' => $this->last_session];
		$this->session_model->update_session($this->session_from_database['username'], $data);
	}

	public function getLastSession() {
		return $this->last_session;
	}

	public function setUserAccount($user_account) {

		$this->username = $user_account->username;
		$this->setJID($user_account->JID);
	}

	public function setMessage($message) {

		$this->message = $message;
	}
}