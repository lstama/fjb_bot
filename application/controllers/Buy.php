<?php
/**
 * Created by PhpStorm.
 * User: tes
 * Date: 27/07/2017
 * Time: 16.57
 */

include_once 'FJB.php';

class Buy extends FJB {

	protected function displayBarang($barang) {

		$title = $barang['thread']['title'];
		$price = "Harga : " . $this->toRupiah($barang['thread']['discounted_price']);
		if ($barang['thread']['discount'] > 0) {

			$price .= "\nHarga sebelum diskon : " . $this->toRupiah($barang['thread']['item_price']);
		}
		$image_thumbnail = $barang['thread']['resources']['thumbnail'];
		$interactive = $this->session->createInteractive($image_thumbnail, $title, $price);
		$this->session->sendInteractiveMessage($interactive);
	}

	protected function sendCheckoutUrl($response, $buy) {

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

	protected function sendQuantity() {

		$this->session->sendMessage('Silakan masukkan jumlah barang yang akan dibeli. (1 - 99)');
	}

	protected function checkQuantity() {

		$jumlah = $this->session->message;

		if ((!is_numeric($jumlah)) or ($jumlah < 1) or ($jumlah > 99)) {

			$this->session->sendMessage('Jumlah tidak valid.');
			$this->sendQuantity();
			return 'failed';
		}

		$quantity = ['quantity' => $jumlah];
		$this->session->buy_model->update_buy($this->session->username, $quantity);

		return 'success';
	}

	protected function getBarang($thread_id) {

		$response = $this->get('v1/lapak/' . $thread_id, []);
		if (!$response->isSuccess()) return $response;
		$response = $response->getContent();

		return $response;
	}

	protected function sendDaftarAlamat() {

		$response = $this->get('v1/fjb/location/addresses');
		if (!$response->isSuccess()) return false;
		$response = $response->getContent();

		$counter = 0;
		$multiple_interactive = [];
		foreach ($response['data'] as $alamat) {

			$counter += 1;
			if ($counter == 11) break;

			$name = $alamat['name'];
			$buttons = [$this->session->createButton($alamat['id'], 'Pilih Alamat')];

			if ($alamat['default']) {

				$name .= '(Alamat Utama)';
			}
			$temp = $this->session->createInteractive(null, $name, $alamat['address'], $buttons);

			array_push($multiple_interactive, $temp);

		}

		if ($counter == 0) {

			$this->sendEmptyAlamatDialog();
			return false;

		}

		$this->session->sendMessage("Silakan pilih alamat tujuan pengiriman.");
		$this->session->sendMultipleInteractiveMessage($multiple_interactive);
		return true;
	}
}