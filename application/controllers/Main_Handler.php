<?php

include_once 'Session.php';
include_once 'FJB_Bot.php';

class Main_Handler {

	public $user_account;
	public $message;

	public function handleReceivedMessage() {

		if ($this->isMessageLengthValid()) {

			#Default
			if ($this->message == 'halo') {

				http_response_code(200);
				header('Content-Type: application/json');
				$data = ["body" => 'halo juga!'];
				$data = json_encode($data);

				echo $data;
				return;
			}

			$session = $this->createSession();
			if ($session->isLoggedOn()) {

				$bot = new FJB_Bot;
				$bot->setMessageNow($session->message);
				$bot->setSessionNow($session->last_session);
				$bot->setSession($session);
				$bot->main();
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