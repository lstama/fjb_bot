<?php

include_once 'User_Account.php';
include_once 'Main_Handler.php';
include_once 'Authorize_Session.php';

class Kaskus_Hooks extends CI_Controller {

	public $request_header;
	public $http_body;
	public $request_body;
	public $oauth_token;
	public $oauth_verifier;
	public $token;
	private $handler;
	private $authorize_session;

	public function __construct() {

		parent::__construct();

		if ($this->input->server('REQUEST_METHOD') == 'POST') {

			$this->request_header = $this->input->request_headers();
			$this->http_body = file_get_contents('php://input');
			$this->request_body = json_decode($this->http_body);
			$this->handler = new Main_Handler;
		}

		if ($this->input->server('REQUEST_METHOD') == 'GET') {

			$this->oauth_token = $this->input->get('oauth_token', TRUE);
			$this->oauth_verifier = $this->input->get('oauth_verifier', TRUE);
			$this->token = $this->input->get('token', TRUE);
			$this->authorize_session = new Authorize_Session;
		}
	}

	public function handleKaskusChatRequest() {

		$hook_secret = $this->getHookSecret();
		$http_body = $this->http_body;
		$http_date = $this->request_header['Date'];
		$request_signature = $this->request_header["Obrol-signature"];
		$bot_signature = $this->generateKaskusChatSignature($hook_secret, $http_body, $http_date);

		if ($request_signature == $bot_signature) {

			$user_account = $this->getUserAccount();
			$message = $this->getMessage();

			$this->handler->setUserAccount($user_account);
			$this->handler->setMessage($message);
			$this->handler->handleReceivedMessage();
		} else {

			echo 'failed on chat hook';
		}

	}

	private function getHookSecret() {

		return getenv('BOT_HOOK_SECRET');
	}

	private function generateKaskusChatSignature($hook_secret, $http_body, $http_date) {

		$string_to_encode = $http_date . $http_body;
		$hashed_string = base64_encode(hash_hmac('sha256', $string_to_encode, $hook_secret, true));

		return $hashed_string;
	}

	public function getMessage() {

		return $this->request_body->body;
	}

	public function getUserAccount() {

		$user_account = new user($this->request_body->from, $this->request_body->fromPlain);
		return $user_account;
	}

	public function handleKaskusWebRedirect() {

		#Hanya untuk kasus redirect

		$this->authorize_session->setToken($this->token);
		$this->authorize_session->setOauthToken($this->oauth_token);
		$this->authorize_session->setOauthVerifier($this->oauth_verifier);
		$this->authorize_session->authorizeSession();
	}
}