<?php

class Main_Handler {

	public $content;

	public function __construct($content) {

		$this->content = $content;
	}

	public function handleReceivedMessage() {

		if ($message == 'halo') {

			#sendReply($user, 'Hai '.$user->username.'!');
			return;
		}

		$session = new User_Session($content);

		if ($session->status === 'logged_on') {

			mainFunction($content, $session);

		} else {

			#Send authorize url
		}
	}

	public function mainFunction($content, $session) {

		#Hello world
	}
}