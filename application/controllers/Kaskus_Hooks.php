<?php

include_once 'User_Account.php';
include_once 'Main_Handler.php';

class Kaskus_Hooks {

	public $request_header;
	public $request_body;
	public $oauth_token;
	public $oauth_verifier;
	public $token;
	private $handler;
	private $session;

	public function __construct() {

		if ($this->input->server('REQUEST_METHOD') == 'POST') {

			$this->request_header 	= $this->input->request_headers();
			$body 					= file_get_contents('php://input');
			$this->request_body 	= json_decode($body);
			$this->handler			= new Main_Handler;
		}

		if ($this->input->server('REQUEST_METHOD') == 'GET') {

			$this->oauth_token 		= $this->input->get('oauth_token', TRUE);
			$this->oauth_verifier 	= $this->input->get('oauth_verifier', TRUE);
			$this->token 			= $this->input->get('token', TRUE);
			$this->session			= new Session;
		}
	}

	public function handleKaskusChatRequest() {

		$hook_secret 			= $this->getHookSecret();
		$http_body 				= $this->request_body;
		$http_date 				= $this->request_header['Date'];
		$request_signature 		= $this->request_header["Obrol-signature"];
		$bot_signature 			= $this->generateKaskusChatSignature($hook_secret, $http_body, $http_date);

		if ($request_signature == $bot_signature) {

			$user_account = $this->getUserAccount();
			$message = $this->getMessage();

			$this->handler->setUserAccount($user_account);
			$this->handler->setMessage($message);
			$this->handler->handleReceivedMessage();
		}
		else {

			echo 'failed on chat hook';
		}

	}

	private function getHookSecret() {

		return getenv('BOT_HOOK_SECRET');
	}

	private function generateKaskusChatSignature($hook_secret, $http_body, $http_date) {

		$string_to_encode = $http_date . $http_body;
		$hashed_string 	= base64_encode(hash_hmac('sha256', $string_to_encode, $hook_secret, true));

		return $hashed_string;
	}

	public function getMessage() {

		return $this->request_body->body;
	}

	public function getUserAccount() {

		$user_account = new User_Account($this->request_body->from, $this->request_body->fromPlain);
		return $user_account;
	}

	public function handleKaskusWebRedirect() {

		#Hanya untuk kasus redirect
		$user_account 	= new User_Account($this->oauth_verifier, $this->oauth_token);

		$message 		= $this->token;
		$this->session->setUserAccount($user_account);
		$this->session->setMessage($message);
		$this->session->authorizeSession();
	}   
}