<?php

include_once 'FJB.php';
include_once 'Buy_Instant.php';
include_once 'Buy_Normal.php';

class Buy_Start extends FJB {

	public function main() {

		$message_prefix = $this->getPrefix($this->message_now);
		$message_suffix = $this->getSuffix($this->message_now);

		switch ($message_prefix) {

			case 'start':

				$this->startBuy($message_suffix);
				break;

			default:

				$this->lastSessionSpecific();
		}
	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			case 'instant':
				$buy_instant = new Buy_Instant();
				$buy_instant->setMessageNow($this->message_now);
				$buy_instant->setSessionNow($session_suffix);
				$buy_instant->setSession($this->session);
				$buy_instant->lastSessionSpecific();
				break;

			case 'normal':
				$buy_normal = new Buy_Normal();
				$buy_normal->setMessageNow($this->message_now);
				$buy_normal->setSessionNow($session_suffix);
				$buy_normal->setSession($this->session);
				$buy_normal->lastSessionSpecific();
				break;

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	public function startBuy($thread_id) {


		$buy = $this->session->buy_model->find_buy($this->session->username);

		if (empty($buy)) {

			$this->session->buy_model->create_buy(['user' => $this->session->username]);
		}

		$this->session->buy_model->update_buy($this->session->username, ['thread_id' => $thread_id]);

		$thread_type = $this->getThreadType($thread_id);

		switch ($thread_type) {

			case 'instant':
				$buy_instant = new Buy_Instant();
				$buy_instant->setMessageNow($this->message_now);
				$buy_instant->setSessionNow($this->session_now);
				$buy_instant->setSession($this->session);
				$buy_instant->instantBuy();
				break;

			case 'normal':
				$buy_normal = new Buy_Normal();
				$buy_normal->setMessageNow($this->message_now);
				$buy_normal->setSessionNow($this->session_now);
				$buy_normal->setSession($this->session);
				$buy_normal->normalBuy();
				break;

			case 'not_found':
				$this->sendThreadNotFoundDialog();
				break;

			default:
				break;
		}
	}

	public function getThreadType($thread_id) {

		$response = $this->get('v1/lapak/' . $thread_id, []);
		if (!$response->isSuccess()) return 'error';
		$response = $response->getContent();

		if (isset($response['thread']['is_instant_purchase'])) {

			if ($response['thread']['is_instant_purchase']) {

				return 'instant';
			}
		}

		if ($response['thread']['open'] == 0) {

			return 'not_found';
		}

		return 'normal';
	}

	public function sendThreadNotfoundDialog() {

		$this->session->setLastSession('menu');
		$title = 'Terjadi Kesalahan';
		$caption = 'Lapak tidak ada atau lapak sudah ditutup. Silakan Kembali ke menu utama.';
		$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);
		$this->session->sendInteractiveMessage($interactive);
	}

}