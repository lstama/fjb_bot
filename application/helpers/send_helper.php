<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('sendReply'))
{
    function sendReply($user, $message) {

		http_response_code(200);
		header('Content-Type: application/json');
		$data = ["body" => $message];
		
		echo json_encode($data);

	}   
}

if ( ! function_exists('sendMessage'))
{
    function sendMessage($bot_account, $user, $message) {

    	$temp = & get_instance();
		$temp->load->helper('auth_helper');
		$temp->load->helper('request_helper');

		$auth = basicAuthHeader($bot_account->username, $bot_account->password);
		
		$content_type  = "application/json";

		$body['id'] 	 		 = $bot_account->bot_id;
		$recipients['body'] 	 = $message;
		$recipients['recipient'] = $user->JID;
		$body['sendList']		 = array($recipients);
		$body 					 = json_encode($body);

		$result = sendToChatApi($auth, $content_type, $body);

	}   
}

