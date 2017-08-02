<?php

include_once 'Session.php';
include_once 'FJB_Bot.php';
include_once 'Sender.php';

class Main_Handler extends Sender {

	private $user_account;
	private $message;

	public function handleReceivedMessage() {

		if ($this->isMessageLengthValid()) {

			#Default
			if ($this->message == 'halo') {

				$this->sendReply('Halo juga!');
				return;
			}

			$session = $this->createSession();
			if ($session->isLoggedOn()) {

				$bot = new FJB_Bot;
				$bot->setMessageNow($session->message);
				$bot->setSessionNow($session->getLastSession());
				$bot->setSession($session);
				$bot->main();
			}
		} else {

			$this->sendReply('Bot tidak dapat menerima pesan yang lebih dari 100 karakter.');
			return;
		}

	}

	private function isMessageLengthValid() {

		if (strlen($this->message) <= 100) {

			return true;
		} else {

			return false;
		}
	}

	private function createSession() {

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