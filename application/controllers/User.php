<?php

class User {

	public $JID 			= '';
	public $username 		= '';
	public $token			= '';
	public $token_secret	= '';
	public $last_session	= '';
	
	public function __construct($JID, $username) {

		$this->JID = $JID;
		$this->username = $username;
	}
}