<?php

include_once 'Buy.php';

class Buy_Instant extends Buy {

	public function instantBuy() {

		$this->session->setLastSession('buy_instant_quantity');
		$this->sendQuantity();
		return;

	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			case 'quantity':
				$this->selectQuantity();
				break;

			case 'confirmation':
				$this->selectConfirmation();
				break;

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	public function selectQuantity() {

		$result = $this->checkQuantity();
		if ($result == 'failed') return;

		$this->session->setLastSession('buy_instant_confirmation');
		$this->sendInstantConfirmation();
		return;
	}

	public function sendInstantConfirmation() {

		$result = $this->session->buy_model->find_buy($this->session->username);

		$barang = $this->getBarang($result['thread_id']);
		$this->displayBarang($barang);

		$price = $barang['thread']['discounted_price'];

		$total_price = $result['quantity'] * $price;
		$biaya = 'Total barang : ' . $result['quantity'];
		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);

		$this->session->sendMessage($biaya);

		$buttons = [
			$this->session->createButton('ya', 'Ya'),
			$this->session->createButton('/buy_start_' . $result['thread_id'], 'Ubah Data'),
			$this->session->createButton('/menu', 'Tidak')
		];
		$title = 'Apakah data di atas sudah benar?';
		$caption = "Kaskus tidak bertanggung jawab atas data yang salah.";
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);
		$this->session->sendInteractiveMessage($interactive);

		return;
	}

	public function selectConfirmation() {

		if ($this->session->message == 'ya') {

			$buy = $this->session->buy_model->find_buy($this->session->username);

			$parameter = [
				'quantity' => $buy['quantity'],
				'tips' => '0'
			];

			$response = $this->post('v1/fjb/lapak/' . $buy['thread_id'] . '/buy_now', $parameter);
			if (!$response->isSuccess()) return;
			$response = $response->getContent();

			$this->sendCheckoutUrl($response, $buy);
		}
	}

}