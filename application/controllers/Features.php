<?php

include_once 'Request.php';

class Features extends Request {

	protected $message_now;
	protected $session_now;

	protected function getPrefix($command) {

		$temp = explode('_', $command, 2);
		if (isset($temp[0])) {

			return $temp[0];
		}
		return '';
	}

	protected function getSuffix($command) {

		$temp = explode('_', $command, 2);
		if (isset($temp[1])) {

			return $temp[1];
		}
		return '';
	}

	protected function sendUnrecognizedCommandDialog() {

		$this->session->setLastSession('unrecognized_command');

		$buttons 	 = array($this->session->createButton('/menu', 'Kembali ke Menu Utama'));
		$title 		 = "Perintah Tidak Dikenal";
		$caption	 = "Silakan masukkan perintah yang benar atau kembali ke menu utama.";
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

		$this->session->sendInteractiveMessage($interactive);
		return;
	}

	public function getMessageNow() {

		return $this->message_now;
	}


	public function setMessageNow($message_now) {

		$this->message_now = $message_now;
	}


	public function getSessionNow() {

		return $this->session_now;
	}


	public function setSessionNow($session_now) {

		$this->session_now = $session_now;
	}

}