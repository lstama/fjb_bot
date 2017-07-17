<?php

include 'User.php';
include 'Bot_Account.php';
include 'Main_Handler.php';

class Kaskus_Hooks extends CI_Controller {

	public function __construct() {

		parent::__construct();
		
		$this->bot_account 		= new Bot_Account;
		$this->request_header 	= $this->input->request_headers();
		$body 					= file_get_contents('php://input');
		$body 					= json_decode($body);
		$this->request_body 	= $body->body;

		if ($this->input->server('REQUEST_METHOD') == 'GET') {
			$this->oauth_token 		= $this->input->get('oauth_token', TRUE);
			$this->oauth_verifier 	= $this->input->get('oauth_verifier', TRUE);
			$this->token 			= $this->input->get('token', TRUE);
		}
	}

	public function getContent($user_chat_account, $message) {

		$user_chat_account 	= $this->getUserChatAccount();
		$message 			= $this->getMessage();

		#NOWIMHERE
		#bikin class chat buat handle di bawah ini (ada redirect)
		return [

				'bot_account' 		=> $this->bot_account,
				'user_chat_account'	=> $user_chat_account,
				'message'	  		=> $message
			];
	}

	public function getMessage() {

		return $this->request_body;
	}

	public function getUserChatAccount() {

		$user_chat_account 			= new User($this->request_body->from, $this->request_body->fromPlain);
		return $user_chat_account;
	}

	public function isSignatureSame(){

		
	}

	public function handleKaskusChatRequest()() {

		$hook_secret 			= $this->bot_account->hook_secret;
		$http_body 				= $this->request_body;
		$http_date 				= $this->request_header['Date'];
		$request_signature 		= $this->request_header["Obrol-signature"];
		$bot_signature 			= $this->generateKaskusChatSignature($hook_secret, $http_body, $http_date);

		if ($request_signature == $bot_signature) {

			$content 		= $this->getContent();
			$handler       	= new Main_Handler($content);
			
			$handler->handleReceivedMessage();
			
		} 

		else {

			echo 'failed on chat hook';
		}

	}

	public function generateKaskusChatSignature($hook_secret, $httpBody, $httpDate) {
    	
    	$stringToEncode = $httpDate . $httpBody;
    	$hashedString 	= base64_encode(hash_hmac('sha256', $stringToEncode, $hook_secret, true));
    	
        return $hashedString;

	}

	public function main_hook() {

		if ($this->input->server('REQUEST_METHOD') == 'GET') {

			$oauth_token 	= $this->input->get('oauth_token', TRUE);
			$oauth_verifier = $this->input->get('oauth_verifier', TRUE);
			$token 			= $this->input->get('token', TRUE);

			$bot_account	= new Bot_Account;
			$user 			= new User($oauth_verifier, $oauth_token);
			$message 		= $token;
			$content 		= ['bot_account' => $bot_account, 'user' => $user, 'message' => $message, 'redirect' => true];
			$session 		= new User_Session($content);
			$session->authorizeSession();

		}
	}   
}