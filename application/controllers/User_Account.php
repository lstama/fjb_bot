<?php

class User_Account {

	public $JID 			= '';
	public $username 		= '';
	
	public function __construct($JID, $username) {

		$this->JID = $JID;
		$this->username = $username;
	}
}