<?php

require 'vendor/autoload.php';
use GuzzleHttp\Client;

class Sender extends Bot_Account {

    private $client;
	public $JID;

    public function __construct() {

    	parent::__construct();
    	$this->client = new Client(['http_errors' => false]);
    }

	public function sendInteractiveReply($message) {

		$data['interactive'] = $message;
		$this->sendReply($message);
	}

	public function sendMultipleInteractiveReply($message) {

		$data['interactives'] = $message;
		$this->sendReply($message);
	}

	public function sendReply($message) {

		http_response_code(200);
		header('Content-Type: application/json');
		$data = ["body" => $message];
		$data = json_encode($data);

		echo $data;
	}

	public function sendInteractiveMessage($message) {

		$data['interactive'] = $message;
		$this->sendMessage($message);
	}

	public function sendMultipleInteractiveMessage($message) {

		$data['interactives'] = $message;
		$this->sendMessage($message);
	}

	public function sendMessage($message) {

		$auth					 = $this->basicAuthHeader();

		$content_type  			 = "application/json";

		$body['id'] 	 		 = $this->bot_id;
		$recipients['body'] 	 = $message;
		$recipients['recipient'] = $this->JID;
		$body['sendList']		 = [$recipients];

		$body 					 = json_encode($body);

		$this->sendToChatApi($auth, $content_type, $body);
	}

	public function basicAuthHeader() {

		$string_to_encode	= $this->bot_username . ':' . $this->bot_password;
		$hashed_string		= base64_encode($string_to_encode);

		return 'Basic ' . $hashed_string;
	}

	public function sendToChatApi($auth, $content_type, $body) {

		$header["Content-Type"]  = $content_type;
		$header["Authorization"] = $auth;
		$option					 = ['verify' => false, 'headers' => $header, 'body' => $body];

		$result 		 		 = $this->client->post($this->send_mass_api, $option);
		return $result;	
	}

	public function requestPost($url, $headers = null, $body = null) {

		$result = $this->client->post($url, ['verify' => false, 'headers' => $headers, 'body' => $body]);

		return $result;
		
	}      

	public function requestGet($url, $headers =  null, $query = null) {

    	$result = $this->client->get($url, ['verify' => false, 'headers' => $headers, 'query' => $query]);
    	
		return $result;
	}

	public function createButton($reply, $label, $show = 'all') {

    	$b['reply'] = $reply;
    	$b['text']  = $label;
    	$b['show']	= $show;
    	$b['client']= 'OTHER';

    	return $b;
	}

	public function createInteractive($image = null, $title = null, $caption = null, $buttons = null, $placeholder = null) {

    	if ($image != null)       $i['image']          = $image;
    	if ($title != null)       $i['title']          = $title;
    	if ($caption != null)     $i['caption']        = $caption;
    	if ($buttons != null)     $i['buttons']        = $buttons;
    	if ($placeholder != null) $i['placeholder']    = $placeholder;

    	return $i;
	}

	public function setJID($JID) {

		$this->JID = $JID;
	}
}