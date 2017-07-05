<?php

include 'User_Session.php';
include_once 'Sender.php';

class Main_Handler {

	public $content;
	public $session;

	public function __construct($content) {

		$this->content = $content;
	}

	public function handleReceivedMessage() {

		if ($this->content['message'] == 'halo') {

			$sender = new Sender;
			$sender->sendReply('Hai '.$this->content['user']->username.'!');
			return;
		}

		$this->session = new User_Session($this->content);

		if ($this->session->status === 'logged_on') {

			$this->mainFunction();

		} else {

			#Send authorize url
		}
	}

	public function mainFunction() {

		$sender = new Sender;
		$sender->sendReply('Halo '.$this->session->logged_on_user.'!');
	}
}