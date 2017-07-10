<?php

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class Sender {

	public function sendToChatApi($auth, $content_type, $body) {

    	$client = new Client(['http_errors' => false]);
    	
		$header["Content-Type"]  = $content_type;
		$header["Authorization"] = $auth;
		$result 		 		 = $client->post('https://api.obrol.id/api/v1/bot/send-mass', ['verify' => false, 'headers' => $header, 'body' => $body]);
		
		return $result;	
	}

	public function requestPost($url, $headers = null, $body = null) {

    	$client = new Client(['http_errors' => false]);

		$result = $client->post($url, ['verify' => false, 'headers' => $headers, 'body' => $body]);

		return $result;
		
	}      

	public function requestGet($url, $headers =  null, $query = null) {

		$client = new Client(['http_errors' => false]);

    	$result = $client->get($url, ['verify' => false, 'headers' => $headers, 'query' => $query]);	
    	
		return $result;
	} 

	public function sendReply($message, $placeholder = null) {

		http_response_code(200);
		header('Content-Type: application/json');
		$data = ["body" => $message, 'placeholder' => $placeholder];
		
		echo json_encode($data);

	}

	public function sendMessage($bot_account, $user, $message) {

    	$auth = $this->basicAuthHeader($bot_account->username, $bot_account->password);
		
		$content_type  = "application/json";

		$body['id'] 	 		 = $bot_account->bot_id;
		$recipients['body'] 	 = $message;
		$recipients['recipient'] = $user->JID;
		$body['sendList']		 = array($recipients);
		$body 					 = json_encode($body);

		$result = $this->sendToChatApi($auth, $content_type, $body);

	}

	public function basicAuthHeader($username, $password) {
    	
    	$stringToEncode = $username . ':' . $password;
    	$hashedString 	= base64_encode($stringToEncode);
    	
        return 'Basic ' . $hashedString;

	}

	public function button($reply, $label, $show = 'all') {

    	$b['reply'] = $reply;
    	$b['text']  = $label;
    	$b['show']	= $show;
    	$b['client']= 'OTHER';

    	return $b;
	}

	public function interactive($image = null, $title = null, $caption = null, $buttons = null, $placeholder = null) {

    	if ($image != null)       $i['image']          = $image;
    	if ($title != null)       $i['title']          = $title;
    	if ($caption != null)     $i['caption']        = $caption;
    	if ($buttons != null)     $i['buttons']        = $buttons;
    	if ($placeholder != null) $i['placeholder']    = $placeholder;

    	return $i;
	}
}