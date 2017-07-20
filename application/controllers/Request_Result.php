<?php

class Request_Result {

	public $success;
	public $content;

	public function __construct($success = null, $content = null) {

		$this->setSuccess($success);
		$this->setContent($content);
	}

	public function getSuccess() {

		return $this->success;
	}

	public function isSuccess() {

		return $this->success;
	}

	public function setSuccess($success) {

		$this->success = $success;
	}

	public function getContent() {

		return $this->content;
	}

	public function setContent($content) {

		$this->content = $content;
	}

}