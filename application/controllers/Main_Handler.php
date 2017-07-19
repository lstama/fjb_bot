<?php

class Main_Handler {

	public $user_account;
	public $message;
	private $fjb_bot;

	public function __construct() {

		$this->fjb_bot = new FJB_Bot;
	}

	public function handleReceivedMessage() {

		if ($this->isMessageLengthValid()) {

			#Default
			if ($this->message == 'halo') {

				echo 'Halo juga!';
				return;
			}

			$session = $this->createSession();
			if ($session->isLoggedOn()) {

				#call the bot
			} else {

				#do something
			}
		}

	}

	public function isMessageLengthValid() {

		if (strlen($this->message) <= 100) {

			return true;
		}
		else {

			return false;
		}
	}

	public function createSession() {

		$session = new Session();
		$session->setUserAccount($this->user_account);
		$session->setMessage($this->message);
		return $session;
	}

	public function setUserAccount($user_account) {

		$this->user_account = $user_account;
	}

	public function setMessage($message) {

		$this->message = $message;
	}

}