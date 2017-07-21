<?php

include_once 'FJB.php';

class Buy extends FJB {

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

	public function startBuy($thread_id) {
		
		$buy = $this->session->buy_model->find_buy($this->session->username);

		if (empty($buy)) {

			$this->session->buy_model->create_buy(['user' => $this->session->username]);
		}

		$this->session->buy_model->update_buy($this->session->username, ['thread_id' => $thread_id]);

		$thread_type = $this->getThreadType($thread_id);

		if ($thread_type == 'instant') {

			$this->instantBuy();
			return;
		}

		if ($thread_type == 'normal') {

			$this->normalBuy();
			return;
		}
	}

	public function getThreadType($thread_id) {

		$response = $this->get('v1/lapak/' . $thread_id, []);
		if (! $response->isSuccess()) return 'forbidden';
		$response = $response->getContent();

		if (isset($response['thread']['is_instant_purchase'])) {

			if ($response['thread']['is_instant_purchase']) {

				return 'instant';
			}
		}

		return 'normal';
	}

	public function normalBuy() {

		$response = $this->get('v1/fjb/location/addresses');
		if (! $response->isSuccess()) return;
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

			$buttons = [
				$this->session->createButton('/alamat_create', 'Buat Alamat Baru'),
				$this->session->createButton('/menu', 'Kembali ke Menu Utama.')
			];
			$caption = "Anda belum mempunyai alamat yang tersimpan.\nSilakan menambahkan alamat baru.";
			$interactive = $this->session->createInteractive(null, null, $caption, $buttons);
			$this->session->sendInteractiveMessage($interactive);
			return;

		}

		$this->session->sendMessage("Silakan pilih alamat tujuan pengiriman.");
		$this->session->sendMultipleInteractiveMessage($multiple_interactive);
    	$this->session->setLastSession('buy_alamat');
    	return;
	}

	public function instantBuy() {

		$this->session->setLastSession('buy_quantity_instant');
		$this->sendQuantity();
		return;

	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

		    case 'alamat':
		        $this->selectAlamat();
		        break;

		    case 'quantity':
		        $this->selectQuantity($session_suffix);
		        break;

		    case 'shipping':
		        $this->selectShippingAgent();
		        break;

			case 'confirmation':
				$this->selectConfirmation($session_suffix);
				break;

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	public function selectAlamat() {

		$alamat_id = $this->session->message;

		$result = $this->getAlamat($alamat_id);

		if ($result->isSuccess()) {

			$result = $result->getContent();
			$alamat = [
					'buyer_name' => $result['owner_name'],
					'buyer_phone' => $result['owner_phone'],
					'dest_id' => $result['area_id'],
					'address_id' =>$result['id']
			];
			$this->session->buy_model->update_buy($this->session->username, $alamat);

			$this->session->setLastSession('buy_quantity');
			$this->sendQuantity();
			return;
		}

		$this->session->sendReply('Alamat tidak valid.');

		$buy = $this->session->buy_model->find_buy($this->session->username);
		$this->startBuy($buy['thread_id']);
		return;
	}

	public function getAlamat($id) {

		$response = $this->get('v1/fjb/location/addresses');
		if (! $response->isSuccess()) return $response;
		$result = $response;
		$response = $response->getContent();


		foreach ($response['data'] as $alamat) {

			if ($alamat['id'] == $id) {

				$result->setContent($alamat);
				return $result;
			}
		}

		return $result;
	}

	public function sendQuantity() {

		$this->session->sendMessage('Silakan masukkan jumlah barang yang akan dibeli. (1 - 99)');
	}

	public function selectQuantity($buy_type) {

		$jumlah = $this->session->message;

		if ((! is_numeric($jumlah)) or ($jumlah < 1) or ($jumlah > 99)) {

			$this->session->sendMessage('Jumlah tidak valid.');
			$this->sendQuantity();
			return;
		}

		$quantity = ['quantity' => $jumlah];
		$this->session->buy_model->update_buy($this->session->username, $quantity);

		if ($buy_type == 'instant') {

			$this->session->setLastSession('buy_confirmation_instant');
			$this->sendInstantConfirmation();
			return;
		}
		else {

			$this->session->setLastSession('buy_shipping');
			$this->sendShipping();
			return;
		}
	}

	public function sendShipping() {

		$buy = $this->session->buy_model->find_buy($this->session->username);

		$query = [
			'query' => [
				'thread_id' => $buy['thread_id'],
				'dest_id' => $buy['dest_id'],
				'quantity' => $buy['quantity']
			]
		];

		$response = $this->get('v1/fjb/lapak/' . $buy['thread_id'] . '/shipping_costs', $query);
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

		$counter = 0;
		$multiple_interactive = [];
		foreach ($response['data'] as $jasa) {

			if ($counter == 10) break;
			if ($jasa['type'] != 'others') {

				foreach ($jasa['methods'] as $key => $jenis_jasa) {

					if ($counter == 10) break;
					$counter += 1;
					$buttons = [$this->session->createButton($jenis_jasa['id'], 'Pilih Jasa Pengiriman')];
					$text = $this->toRupiah($jenis_jasa['cost']);

					if ($jenis_jasa['estimation_time'] != "") {

						$text .= "\n" . $jenis_jasa['estimation_time'] . " hari";
					}

					$tmp = $this->session->createInteractive($jasa['image'], $jenis_jasa['name'], $text, $buttons);
					array_push($multiple_interactive, $tmp);
				}
			}
		}

		$this->session->sendMessage("Silakan pilih metode pengiriman.");
		$this->session->sendMultipleInteractiveMessage($multiple_interactive);
	}

	public function selectShippingAgent() {

		$choice = $this->session->message;
		$buy = $this->session->buy_model->find_buy($this->session->username);

		$query = [
			'query' => [
				'thread_id' => $buy['thread_id'],
				'dest_id' => $buy['dest_id'],
				'quantity' => $buy['quantity']
			]
		];
		$response = $this->get('v1/fjb/lapak/' . $buy['thread_id'] . '/shipping_costs', $query);
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

		$jasa_exist = false;
		$jenis_jasa = [];
		foreach ($response['data'] as $jasa) {

			if ($jasa_exist) break;
			if ($jasa['type'] != 'others') {

				foreach ($jasa['methods'] as $key => $value) {

					if ($value['id'] == $choice) {
						$jasa_exist = true;
						$jenis_jasa['method'] = $value;
						$jenis_jasa['image'] = $jasa['image'];
						break;
					}
				}
			}
		}

		if (!$jasa_exist) {

			#TODO : ada tombol balik ke menu
			$this->session->sendMessage("Metode pengiriman tidak valid.");
			$this->sendShipping();
			return;
		}

		$this->session->buy_model->update_buy($this->session->username, ['shipping_id' => $choice]);
		$this->session->setLastSession('buy_confirmation');
		$this->sendNormalConfirmation($jenis_jasa);
	}

	public function sendNormalConfirmation($jasa) {

		#TODO
		$result = $this->session->buy_model->find_buy($this->session->username);

		$alamat = $this->getAlamat($result['address_id']);

		$this->displayAlamat($alamat);

		$this->displayJasaPengiriman($jasa);

		$barang = $this->getBarang($result['thread_id']);
		$this->displayBarang($barang);

		$price = $barang['thread']['discounted_price'];
		$total_price = $result['quantity'] * $price;
		$biaya = 'Total barang : ' . $result['quantity'];
		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);
		$biaya .= "\nBiaya pengiriman : " . $this->toRupiah($jasa['method']['cost']);
		$biaya .= "\nTotal biaya : " . $this->toRupiah($jasa['method']['cost'] + $total_price);

		$this->session->sendMessage($biaya);

		$buttons = [
				$this->session->createButton('ya', 'Ya'),
				$this->session->createButton('/buy_' . $result['thread_id'], 'Ubah Data'),
				$this->session->createButton('/menu', 'Tidak')
		];
		$title = 'Apakah data di atas sudah benar?';
		$caption = "Kaskus tidak bertanggung jawab atas data yang salah.";
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

		$this->session->sendMessage($interactive);
		return;
	}

	public function displayAlamat(Request_Result $result) {

		$result = $result->getContent();

		$kecamatan = $this->getAreaName($result['area_id']);
		if (! $kecamatan->isSuccess()) return;
		$kecamatan = $kecamatan->getContent();

		$kota = $this->getCityName($result['city_id']);
		if (! $kota->isSuccess()) return;
		$kota = $kota->getContent();

		$provinsi = $this->getProvinceName($result['province_id']);
		if (! $provinsi->isSuccess()) return;
		$provinsi = $provinsi->getContent();

		$interactive = $this->session->createInteractive(null, $result['name'], $result['owner_name']);
		$this->session->sendInteractiveMessage($interactive);
		$text = $result['address'] . "\n"
			. $kecamatan . ", Kota/Kab " . $kota . "\n"
			. $provinsi
			. "\nTelephone/Handphone: " . $result['owner_phone'];
		$this->session->sendMessage($text);
	}

	public function displayJasaPengiriman($jasa) {

		$text = $this->toRupiah($jasa['method']['cost']);
		if ($jasa['method']['estimation_time'] != "") {

			$text .= "\n" . $jasa['method']['estimation_time'] . " hari";
		}

		$interactive = $this->session->createInteractive($jasa['image'], $jasa['method']['name'], $text);
		$this->session->sendInteractiveMessage($interactive);
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
				$this->session->createButton('/buy_' . $result['thread_id'], 'Ubah Data'),
				$this->session->createButton('/menu', 'Tidak')
		];
		$title = 'Apakah data di atas sudah benar?';
		$caption = "Kaskus tidak bertanggung jawab atas data yang salah.";
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);
		$this->session->sendInteractiveMessage($interactive);

		return;
	}

	public function selectConfirmation($type = 'normal') {

		if ($this->session->message == 'ya') {

			$buy = $this->session->buy_model->find_buy($this->session->username);

			if ($type == 'instant') {

				$parameter = [
					'quantity' => $buy['quantity'],
					'tips' => '0'
				];
			}
			else {

				$parameter = [
					'buyer_name' => $buy['buyer_name'],
					'buyer_phone' => $buy['buyer_phone'],
					'quantity' => $buy['quantity'],
					'tips' => '0',
					'address_id' => $buy['address_id'],
					'shipping_agent' => $buy['shipping_id'],
					'insurance' => '0'
				];
			}

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



//require 'vendor/autoload.php';
//include_once 'Sender.php';
//
//class Buy extends CI_Controller {
//
//	public $session;
//
//	public function __construct($sess) {
//
//		$this->session = $sess;
//		parent::__construct();
//	}
//
//	public function createBuySession($last_session) {
//
//		$last_session = explode('_', $last_session, 2);
//		switch ($last_session[0]) {
//		    case 'alamat':
//		        $this->selectAlamat();
//		        break;
//
//		    case 'quantity':
//		        $this->selectQuantity($last_session[1]);
//		        break;
//
//		    case 'shipping':
//		        $this->selectShippingAgent();
//		        break;
//
//			case 'confirmation':
//				$this->selectConfirmation($last_session[1]);
//				break;
//
//		    default:
//		        $this->unrecognizedCommand();
//		}
//	}
//
//	public function selectConfirmation($type = null) {
//
//		if ($this->session->message == 'ya') {
//
//			$this->load->model('buy_model');
//			$buy = $this->session->buy_model->find_buy($this->session->username);
//
//
//			if ($type == 'instant') {
//
//				$parameter = array(
//					'quantity' => $buy['quantity'],
//					'tips' => '0'
//				);
//			}
//			else {
//
//				$parameter = array(
//					'buyer_name' => $buy['buyer_name'],
//					'buyer_phone' => $buy['buyer_phone'],
//					'quantity' => $buy['quantity'],
//					'tips' => '0',
//					'address_id' => $buy['address_id'],
//					'shipping_agent' => $buy['shipping_id'],
//					'insurance' => '0'
//				);
//			}
//
//
//			$response = $this->post('v1/fjb/lapak/' . $buy['thread_id'] . '/buy_now', $parameter);
//			if (! $response->isSuccess()) return;
//			$response = $response->getContent();
//
//			#var_dump($response);
//			$this->session->setLastSession('buy_checkout_' . $buy['thread_id']);
//			$sender = new Sender();
//			$b = array($this->session->createButton($response['checkout_url'], 'Lanjut ke Pembayaran'), $this->session->createButton('/menu', 'Kembali ke Menu Utama'));
//			$i['interactive'] = $this->session->createInteractive(null, "Pemesanan Berhasil", 'Silakan klik tombol di bawah ini untuk lanjut ke pembayaran', $b, null);
//
//			$this->session->sendReply($i);
//		}
//	}
//
//	public function selectShippingAgent() {
//
//		$choice = $this->session->message;
//
//		$this->load->model('buy_model');
//		$buy = $this->session->buy_model->find_buy($this->session->username);
//
//		#var_dump($buy);
//
//		$query = ['query' => ['thread_id' => $buy['thread_id'], 'dest_id' => $buy['dest_id'], 'quantity' => $buy['quantity']]];
//
//		$response = $this->get('v1/fjb/lapak/' . $buy['thread_id'] . '/shipping_costs', $query);
//		if (! $response->isSuccess()) return;
//		$response = $response->getContent();
//
//		$ada = false;
//		$jasa = [];
//		foreach ($response['data'] as $a) {
//
//			if ($ada) break;
//			if ($a['type'] != 'others') {
//
//				foreach ($a['methods'] as $key => $value) {
//
//					if ($value['id'] == $choice) {
//						$ada = true;
//						$jasa['method'] = $value;
//						$jasa['image'] = $a['image'];
//						break;
//					}
//				}
//			}
//		}
//
//		if (!$ada) {
//
//			$sender = new Sender;
//			$this->session->sendMessage("Metode pengiriman tidak valid.");
//			$this->sendShipping();
//			return;
//		}
//
//		$this->session->buy_model->update_buy($this->session->username, ['shipping_id' => $choice]);
//		$this->session->setLastSession('buy_confirmation');
//		$this->sendNormalConfirmation($jasa);
//	}
//
//	public function sendNormalConfirmation($jasa) {
//
//		$this->load->model('buy_model');
//		$sender = new Sender;
//		$result = $this->session->buy_model->find_buy($this->session->username);
//
//		$alamat = $this->checkAlamat($result['address_id']);
//
//		$this->displayAlamat($alamat);
//
//		$this->displayJasaPengiriman($jasa);
//
//		$price = $this->displayBarang($result['thread_id']);
//
//		$total_price = $result['quantity'] * $price;
//		$biaya = 'Total barang : ' . $result['quantity'];
//		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);
//		$biaya .= "\nBiaya pengiriman : " . $this->toRupiah($jasa['method']['cost']);
//		$biaya .= "\nTotal biaya : " . $this->toRupiah($jasa['method']['cost'] + $total_price);
//
//		$this->session->sendMessage($biaya);
//
//		$b = array(
//				$this->session->createButton('ya', 'Ya'),
//				$this->session->createButton('/buy_' . $result['thread_id'], 'Ubah Data'),
//				$this->session->createButton('/menu', 'Tidak')
//			);
//
//		$i['interactive'] = $this->session->createInteractive(null, 'Apakah data di atas sudah benar?', "Kaskus tidak bertanggung jawab atas data yang salah.", $b, null);
//
//		$this->session->sendMessage($i);
//
//		return;
//	}
//
//	public function sendInstantConfirmation() {
//
//		$this->load->model('buy_model');
//		$sender = new Sender;
//		$result = $this->session->buy_model->find_buy($this->session->username);
//
//		$price = $this->displayBarang($result['thread_id']);
//
//		$total_price = $result['quantity'] * $price;
//		$biaya = 'Total barang : ' . $result['quantity'];
//		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);
//
//		$this->session->sendMessage($biaya);
//
//		$b = array(
//				$this->session->createButton('ya', 'Ya'),
//				$this->session->createButton('/buy_' . $result['thread_id'], 'Ubah Data'),
//				$this->session->createButton('/menu', 'Tidak')
//			);
//
//		$i['interactive'] = $this->session->createInteractive(null, 'Apakah data di atas sudah benar?', "Kaskus tidak bertanggung jawab atas data yang salah.", $b, null);
//
//		$this->session->sendMessage($i);
//
//		return;
//	}
//
//	public function displayJasaPengiriman($jasa) {
//
//		$sender = new Sender;
//		$text = $this->toRupiah($jasa['method']['cost']);
//		// var_dump($value);
//		if ($jasa['method']['estimation_time'] != "") {
//
//			$text .= "\n" . $jasa['method']['estimation_time'] . " hari";
//		}
//
//		$i['interactive'] = $this->session->createInteractive($jasa['image'], $jasa['method']['name'], $text, null, null);
//		$this->session->sendMessage($i);
//	}
//
//	public function displayBarang($thread_id) {
//
//		$response = $this->get('v1/lapak/' . $thread_id, []);
//		if (! $response->isSuccess()) return;
//		$response = $response->getContent();
//
//		#var_dump($response);
//
//		$sender = new Sender;
//		$title = $response['thread']['title'];
//		$price = "Harga : " . $this->toRupiah($response['thread']['discounted_price']);
//
//			if ($response['thread']['discount'] > 0) {
//
//				$price .= "\nHarga sebelum diskon : " . $this->toRupiah($response['thread']['item_price']);
//			}
//
//		$i['interactive'] = $this->session->createInteractive($response['thread']['resources']['thumbnail'], $title, $price, null, null);
//
//		$this->session->sendMessage($i);
//
//		return $response['thread']['discounted_price'];
//	}
//
//	public function displayAlamat($result) {
//
//		$sender = new Sender;
//		#get kecamatan
//		$kecamatan = $this->getArea($result['area_id']);
//		if (! $kecamatan->isSuccess()) return;
//		$kecamatan = $kecamatan->getContent();
//		#get kota
//		$kota = $this->getCity($result['city_id']);
//		if (! $kota->isSuccess()) return;
//		$kota = $kota->getContent();
//		#get provinsi
//		$provinsi = $this->getProvince($result['province_id']);
//		if (! $provinsi->isSuccess()) return;
//		$provinsi = $provinsi->getContent();
//
//		$i['interactive'] = $this->session->createInteractive(null, $result['name'], $result['owner_name'],null,null);
//		$this->session->sendMessage($i);
//		$text = $result['address'] . "\n" . $kecamatan . ", Kota/Kab " . $kota . "\n" . $provinsi . "\nTelephone/Handphone: " . $result['owner_phone'];
//		$this->session->sendMessage($text);
//	}
//
//	public function sendShipping() {
//
//		$this->load->model('buy_model');
//		$buy = $this->session->buy_model->find_buy($this->session->username);
//
//		#var_dump($buy);
//
//		$query = ['query' => ['thread_id' => $buy['thread_id'], 'dest_id' => $buy['dest_id'], 'quantity' => $buy['quantity']]];
//
//		$response = $this->get('v1/fjb/lapak/' . $buy['thread_id'] . '/shipping_costs', $query);
//		if (! $response->isSuccess()) return;
//		$response = $response->getContent();
//
//		$counter = 0;
//		$sender = new Sender;
//		$multiple_interactive = [];
//		foreach ($response['data'] as $a) {
//
//			if ($counter == 10) break;
//			if ($a['type'] != 'others') {
//
//				foreach ($a['methods'] as $key => $value) {
//
//
//					if ($counter == 10) break;
//					$counter += 1;
//					$b = [$this->session->createButton($value['id'], 'Pilih Jasa Pengiriman')];
//					$text = $this->toRupiah($value['cost']);
//					// var_dump($value);
//					if ($value['estimation_time'] != "") {
//
//						$text .= "\n" . $value['estimation_time'] . " hari";
//					}
//					$tmp = $this->session->createInteractive($a['image'], $value['name'], $text, $b, null);
//					array_push($multiple_interactive, $tmp);
//				}
//			}
//		}
//
//		$this->session->sendMessage("Silakan pilih metode pengiriman.");
//
//		$this->session->sendMessage($i);
//	}
//
//	public function toRupiah($number) {
//
//		$money_number = 'Rp ';
//		$money_number .= number_format($number,2,',','.');
//		return $money_number;
//	}
//
//	public function checkAlamat($id) {
//
//		$response = $this->get('v1/fjb/location/addresses');
//		if (! $response->isSuccess()) return;
//		$response = $response->getContent();
//
//		foreach ($response['data'] as $a) {
//
//			if ($a['id'] == $id) {
//
//				return $a;
//			}
//		}
//
//		return 'failed';
//	}
//
//	public function selectAlamat() {
//
//		$alamat_id = $this->session->message;
//
//		$result = $this->checkAlamat($alamat_id);
//
//		if ($result != 'failed') {
//
//			#save
//			$this->load->model('buy_model');
//			$alamat = array(
//					'buyer_name' => $result['owner_name'],
//					'buyer_phone' => $result['owner_phone'],
//					'dest_id' => $result['area_id'],
//					'address_id' =>$result['id']
//				);
//			$this->session->buy_model->update_buy($this->session->username, $alamat);
//
//			$this->session->setLastSession('buy_quantity');
//			$this->sendQuantity();
//			return;
//		}
//
//		$sender = new Sender;
//		$this->session->sendReply('Alamat tidak valid.');
//
//		$this->load->model('buy_model');
//		$buy = $this->session->buy_model->find_buy($this->session->username);
//
//		$this->startBuy($buy['thread_id']);
//		return;
//	}
//
//	public function sendQuantity() {
//
//		$sender = new Sender;
//		$this->session->sendMessage('Silakan masukkan jumlah barang yang akan dibeli. (1 - 99)');
//	}
//
//	public function selectQuantity($type) {
//
//		$jumlah = $this->session->message;
//		$sender = new Sender;
//
//		if ((! is_numeric($jumlah)) or ($jumlah < 1) or ($jumlah > 99)) {
//
//			$this->session->sendMessage('Jumlah tidak valid.');
//			$this->sendQuantity();
//			return;
//		}
//
//		$this->load->model('buy_model');
//		$j = array(
//				'quantity' => $jumlah
//			);
//		$this->session->buy_model->update_buy($this->session->username, $j);
//
//		if ($type == 'instant') {
//
//
//			$this->session->setLastSession('buy_confirmation_instant');
//			$this->sendInstantConfirmation();
//			return;
//		}
//		else {
//
//			$this->session->setLastSession('buy_shipping');
//			$this->sendShipping();
//			return;
//		}
//	}
//
//	public function normalBuy() {
//
//		$response = $this->get('v1/fjb/location/addresses');
//		if (! $response->isSuccess()) return;
//		$response = $response->getContent();
//
//		$sender = new Sender();
//
//		$counter = 0;
//		$multiple_interactive = [];
//		foreach ($response['data'] as $a) {
//
//			$counter += 1;
//			if ($counter == 11) break;
//
//			$n = $a['name'];
//			$b = array($this->session->createButton($a['id'], 'Pilih Alamat'));
//
//			if ($a['default']) {
//
//				$n .= '(Alamat Utama)';
//			}
//			$temp = $this->session->createInteractive(null, $n, $a['address'], $b, null);
//
//			array_push($multiple_interactive, $temp);
//
//		}
//
//		if ($counter == 0) {
//
//			$b = array($this->session->createButton('/alamat_create', 'Buat Alamat Baru'), $this->session->createButton('/menu', 'Kembali ke Menu Utama.'));
//			$io['interactive'] = $this->session->createInteractive(null, null, "Anda belum mempunyai alamat yang tersimpan.\nSilakan menambahkan alamat baru.", $b, null);
//			$i = $io;
//
//			$this->session->sendMessage($i);
//			return;
//
//		}
//
//		$this->session->sendMessage("Silakan pilih alamat tujuan pengiriman.");
//
//		$this->session->sendMessage($i);
//
//    	$this->session->setLastSession('buy_alamat');
//    	return;
//	}
//
//	public function instantBuy() {
//
//		$this->session->setLastSession('buy_quantity_instant');
//		$this->sendQuantity();
//		return;
//
//	}
//
//	public function startBuy($thread_id) {
//
//		$this->load->model('buy_model');
//		$buy = $this->session->buy_model->find_buy($this->session->username);
//
//		if (empty($buy)) {
//
//    		$this->session->buy_model->create_buy(['user' => $this->session->username]);
//    	}
//
//    	$this->session->buy_model->update_buy($this->session->username, ['thread_id' => $thread_id]);
//
//		$thread_type = $this->checkThreadType($thread_id);
//
//		if ($thread_type == 'instant') {
//
//			$this->instantBuy();
//			return;
//		}
//
//		if ($thread_type == 'normal') {
//
//			$this->normalBuy();
//			return;
//		}
//
//		return;
//	}
//
//	public function checkThreadType($thread_id) {
//
//		$response = $this->get('v1/lapak/' . $thread_id, []);
//		if (! $response->isSuccess()) return 'forbidden';
//		$response = $response->getContent();
//
//		if (isset($response['thread']['is_instant_purchase'])) {
//
//			if ($response['thread']['is_instant_purchase']) {
//
//				return 'instant';
//			}
//		}
//
//		return 'normal';
//	}
//
//
//}