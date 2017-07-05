<?php

include 'User.php';
include 'Bot_Account.php';
include 'Main_Handler.php';

class Kaskus_Hooks extends CI_Controller {


	public function hook() {

		if ($this->input->server('REQUEST_METHOD') == 'POST') {

			$bot_account	= new Bot_Account;
			$header 		= $this->input->request_headers();
			$httpBody 		= file_get_contents('php://input');
			$httpDate 		= $header['Date'];
			$signature 		= $header["Obrol-signature"];

			if ($signature == $this->generateKaskusBotSignature($bot_account->hookSecret, $httpBody, $httpDate)) {

				$body 			= json_decode($httpBody);
				$user 			= new User($body->from, $body->fromPlain);
				$message 		= $body->body;
				$content 		= ['bot_account' => $bot_account, 'user' => $user, 'message' => $message, 'redirect' => false];
				$handler       	= new Main_Handler($content);
				
				$handler->handleReceivedMessage();
				
			} 

			else {

				echo 'failed on chat hook';
			}
		}

	}

	public function generateKaskusBotSignature($hookSecret, $httpBody, $httpDate) {
    	
    	$stringToEncode = $httpDate . $httpBody;
    	$hashedString 	= base64_encode(hash_hmac('sha256', $stringToEncode, $hookSecret, true));
    	
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