<?php

class user {

	public $JID 			= '';
	public $username 		= '';
	
	public function __construct($JID, $username) {

		$this->JID = $JID;
		$this->username = $username;
	}
}