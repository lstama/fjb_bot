<?php

include_once 'FJB.php';

class Buy_Instant extends FJB {

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

	public function sendQuantity() {

		$this->session->sendMessage('Silakan masukkan jumlah barang yang akan dibeli. (1 - 99)');
	}

	public function selectQuantity() {

		$jumlah = $this->session->message;

		if ((! is_numeric($jumlah)) or ($jumlah < 1) or ($jumlah > 99)) {

			$this->session->sendMessage('Jumlah tidak valid.');
			$this->sendQuantity();
			return;
		}

		$quantity = ['quantity' => $jumlah];
		$this->session->buy_model->update_buy($this->session->username, $quantity);

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

	public function getBarang($thread_id) {

		$response = $this->get('v1/lapak/' . $thread_id, []);
		if (! $response->isSuccess()) return $response;
		$response = $response->getContent();

		return $response;
	}

	public function displayBarang($barang) {

		$title = $barang['thread']['title'];
		$price = "Harga : " . $this->toRupiah($barang['thread']['discounted_price']);
		if ($barang['thread']['discount'] > 0) {

			$price .= "\nHarga sebelum diskon : " . $this->toRupiah($barang['thread']['item_price']);
		}
		$image_thumbnail = $barang['thread']['resources']['thumbnail'];
		$interactive = $this->session->createInteractive($image_thumbnail, $title, $price);
		$this->session->sendInteractiveMessage($interactive);
	}

	public function selectConfirmation() {

		if ($this->session->message == 'ya') {

			$buy = $this->session->buy_model->find_buy($this->session->username);

			$parameter = [
				'quantity' => $buy['quantity'],
				'tips' => '0'
			];

			$response = $this->post('v1/fjb/lapak/' . $buy['thread_id'] . '/buy_now', $parameter);
			if (! $response->isSuccess()) return;
			$response = $response->getContent();

			$this->session->setLastSession('buy_checkout_' . $buy['thread_id']);

			$buttons = [
				$this->session->createButton($response['checkout_url'], 'Lanjut ke Pembayaran'),
				$this->session->createButton('/menu', 'Kembali ke Menu Utama')
			];
			$title = "Pemesanan Berhasil";
			$caption = 'Silakan klik tombol di bawah ini untuk lanjut ke pembayaran';
			$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

			$this->session->sendInteractiveReply($interactive);
		}
	}

}