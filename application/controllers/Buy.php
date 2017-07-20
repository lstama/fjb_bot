<?php

require 'vendor/autoload.php';
include_once 'Sender.php';

class Buy extends CI_Controller {

	public $session;

	public function __construct($sess) {

		$this->session = $sess;
		parent::__construct();
	}

	public function createBuySession($last_session) {

		$last_session = explode('_', $last_session, 2);
		switch ($last_session[0]) {
		    case 'alamat':
		        $this->selectAlamat();
		        break;

		    case 'quantity':
		        $this->selectQuantity($last_session[1]);
		        break;

		    case 'shipping':
		        $this->selectShippingAgent();
		        break;

			case 'confirmation':
				$this->selectConfirmation($last_session[1]);
				break;

		    default:
		        $this->unrecognizedCommand();
		}
	}

	public function selectConfirmation($type = null) {

		if ($this->session->content['message'] == 'ya') {

			$this->load->model('buy_model');
			$buy = $this->buy_model->find_buy($this->session->content['user']->username);


			if ($type == 'instant') {

				$parameter = array(
					'quantity' => $buy['quantity'],
					'tips' => '0'
				);	
			}
			else {

				$parameter = array(
					'buyer_name' => $buy['buyer_name'],
					'buyer_phone' => $buy['buyer_phone'],
					'quantity' => $buy['quantity'],
					'tips' => '0',
					'address_id' => $buy['address_id'],
					'shipping_agent' => $buy['shipping_id'],
					'insurance' => '0'
				);
			}
			

			$response = $this->post('v1/fjb/lapak/' . $buy['thread_id'] . '/buy_now', $parameter);
			if (! $response['success']) return;
			$response = $response['result'];

			#var_dump($response);
			$this->session->setLastSession('buy_checkout_' . $buy['thread_id']);
			$sender = new Sender();
			$b = array($sender->button($response['checkout_url'], 'Lanjut ke Pembayaran'), $sender->button('/menu', 'Kembali ke Menu Utama'));
			$i['interactive'] = $sender->interactive(null, "Pemesanan Berhasil", 'Silakan klik tombol di bawah ini untuk lanjut ke pembayaran', $b, null);
		
			$sender->sendReply($i);
		}
	}

	public function selectShippingAgent() {

		$choice = $this->session->content['message'];

		$this->load->model('buy_model');
		$buy = $this->buy_model->find_buy($this->session->content['user']->username);

		#var_dump($buy);

		$query = ['query' => ['thread_id' => $buy['thread_id'], 'dest_id' => $buy['dest_id'], 'quantity' => $buy['quantity']]];

		$response = $this->get('v1/fjb/lapak/' . $buy['thread_id'] . '/shipping_costs', $query);
		if (! $response['success']) return;
		$response = $response['result'];

		$ada = false;
		$jasa = [];
		foreach ($response['data'] as $a) {

			if ($ada) break;
			if ($a['type'] != 'others') {

				foreach ($a['methods'] as $key => $value) {
					
					if ($value['id'] == $choice) {
						$ada = true;
						$jasa['method'] = $value;
						$jasa['image'] = $a['image'];
						break;
					}
				}
			}
		}

		if (!$ada) {

			$sender = new Sender;
			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], "Metode pengiriman tidak valid.");
			$this->sendShipping();
			return;
		}

		$this->buy_model->update_buy($this->session->content['user']->username, ['shipping_id' => $choice]);
		$this->session->setLastSession('buy_confirmation');
		$this->sendNormalConfirmation($jasa);
	}

	public function sendNormalConfirmation($jasa) {

		$this->load->model('buy_model');
		$sender = new Sender;
		$result = $this->buy_model->find_buy($this->session->content['user']->username);

		$alamat = $this->checkAlamat($result['address_id']);

		$this->displayAlamat($alamat);

		$this->displayJasaPengiriman($jasa);

		$price = $this->displayBarang($result['thread_id']);

		$total_price = $result['quantity'] * $price;
		$biaya = 'Total barang : ' . $result['quantity'];
		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);
		$biaya .= "\nBiaya pengiriman : " . $this->toRupiah($jasa['method']['cost']);
		$biaya .= "\nTotal biaya : " . $this->toRupiah($jasa['method']['cost'] + $total_price);

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $biaya);

		$b = array(
				$sender->button('ya', 'Ya'),
				$sender->button('/buy_' . $result['thread_id'], 'Ubah Data'),
				$sender->button('/menu', 'Tidak')
			);

		$i['interactive'] = $sender->interactive(null, 'Apakah data di atas sudah benar?', "Kaskus tidak bertanggung jawab atas data yang salah.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);

		return;
	}

	public function sendInstantConfirmation() {

		$this->load->model('buy_model');
		$sender = new Sender;
		$result = $this->buy_model->find_buy($this->session->content['user']->username);
		
		$price = $this->displayBarang($result['thread_id']);

		$total_price = $result['quantity'] * $price;
		$biaya = 'Total barang : ' . $result['quantity'];
		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $biaya);

		$b = array(
				$sender->button('ya', 'Ya'),
				$sender->button('/buy_' . $result['thread_id'], 'Ubah Data'),
				$sender->button('/menu', 'Tidak')
			);

		$i['interactive'] = $sender->interactive(null, 'Apakah data di atas sudah benar?', "Kaskus tidak bertanggung jawab atas data yang salah.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);

		return;
	}

	public function displayJasaPengiriman($jasa) {

		$sender = new Sender;
		$text = $this->toRupiah($jasa['method']['cost']);
		// var_dump($value);
		if ($jasa['method']['estimation_time'] != "") {

			$text .= "\n" . $jasa['method']['estimation_time'] . " hari";
		}

		$i['interactive'] = $sender->interactive($jasa['image'], $jasa['method']['name'], $text, null, null);
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
	}

	public function displayBarang($thread_id) {

		$response = $this->get('v1/lapak/' . $thread_id, []);
		if (! $response['success']) return;
		$response = $response['result'];

		#var_dump($response);

		$sender = new Sender;
		$title = $response['thread']['title'];
		$price = "Harga : " . $this->toRupiah($response['thread']['discounted_price']);

			if ($response['thread']['discount'] > 0) {

				$price .= "\nHarga sebelum diskon : " . $this->toRupiah($response['thread']['item_price']);
			}

		$i['interactive'] = $sender->interactive($response['thread']['resources']['thumbnail'], $title, $price, null, null);

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
	
		return $response['thread']['discounted_price'];
	}

	public function displayAlamat($result) {

		$sender = new Sender;
		#get kecamatan
		$kecamatan = $this->getArea($result['area_id']);
		if (! $kecamatan['success']) return;
		$kecamatan = $kecamatan['result'];
		#get kota
		$kota = $this->getCity($result['city_id']);
		if (! $kota['success']) return;
		$kota = $kota['result'];
		#get provinsi
		$provinsi = $this->getProvince($result['province_id']);
		if (! $provinsi['success']) return;
		$provinsi = $provinsi['result'];

		$i['interactive'] = $sender->interactive(null, $result['name'], $result['owner_name'],null,null);
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		$text = $result['address'] . "\n" . $kecamatan . ", Kota/Kab " . $kota . "\n" . $provinsi . "\nTelephone/Handphone: " . $result['owner_phone'];
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $text);
	}

	public function sendShipping() {

		$this->load->model('buy_model');
		$buy = $this->buy_model->find_buy($this->session->content['user']->username);

		#var_dump($buy);

		$query = ['query' => ['thread_id' => $buy['thread_id'], 'dest_id' => $buy['dest_id'], 'quantity' => $buy['quantity']]];

		$response = $this->get('v1/fjb/lapak/' . $buy['thread_id'] . '/shipping_costs', $query);
		if (! $response['success']) return;
		$response = $response['result'];

		$counter = 0;
		$sender = new Sender;
		$i['interactives'] = [];
		foreach ($response['data'] as $a) {

			if ($counter == 10) break;
			if ($a['type'] != 'others') {

				foreach ($a['methods'] as $key => $value) {
					
					
					if ($counter == 10) break;
					$counter += 1;
					$b = [$sender->button($value['id'], 'Pilih Jasa Pengiriman')];
					$text = $this->toRupiah($value['cost']);
					// var_dump($value);
					if ($value['estimation_time'] != "") {

						$text .= "\n" . $value['estimation_time'] . " hari";
					}
					$tmp = $sender->interactive($a['image'], $value['name'], $text, $b, null);
					array_push($i['interactives'], $tmp);
				}
			}
		}

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], "Silakan pilih metode pengiriman.");

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
	}

	public function toRupiah($number) {

		$money_number = 'Rp ';
		$money_number .= number_format($number,2,',','.');
		return $money_number;
	}

	public function checkAlamat($id) {

		$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		foreach ($response['data'] as $a) {

			if ($a['id'] == $id) {

				return $a;
			}
		}

		return 'failed';
	}

	public function selectAlamat() {

		$alamat_id = $this->session->content['message'];

		$result = $this->checkAlamat($alamat_id);

		if ($result != 'failed') {

			#save
			$this->load->model('buy_model');
			$alamat = array(
					'buyer_name' => $result['owner_name'],
					'buyer_phone' => $result['owner_phone'],
					'dest_id' => $result['area_id'],
					'address_id' =>$result['id']
				);
			$this->buy_model->update_buy($this->session->content['user']->username, $alamat);

			$this->session->setLastSession('buy_quantity');
			$this->sendQuantity();
			return;
		}

		$sender = new Sender;
		$sender->sendReply('Alamat tidak valid.');

		$this->load->model('buy_model');
		$buy = $this->buy_model->find_buy($this->session->content['user']->username);

		$this->startBuy($buy['thread_id']);
		return;
	}

	public function sendQuantity() {

		$sender = new Sender;
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], 'Silakan masukkan jumlah barang yang akan dibeli. (1 - 99)');
	}

	public function selectQuantity($type) {

		$jumlah = $this->session->content['message'];
		$sender = new Sender;

		if ((! is_numeric($jumlah)) or ($jumlah < 1) or ($jumlah > 99)) {

			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], 'Jumlah tidak valid.');
			$this->sendQuantity();
			return;
		}

		$this->load->model('buy_model');
		$j = array(
				'quantity' => $jumlah
			);
		$this->buy_model->update_buy($this->session->content['user']->username, $j);

		if ($type == 'instant') {

			
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

	public function normalBuy() {

		$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		$sender = new Sender();

		$counter = 0;
		$i['interactives'] = [];
		foreach ($response['data'] as $a) {

			$counter += 1;
			if ($counter == 11) break;

			$n = $a['name'];
			$b = array($sender->button($a['id'], 'Pilih Alamat'));

			if ($a['default']) {

				$n .= '(Alamat Utama)';
			}
			$temp = $sender->interactive(null, $n, $a['address'], $b, null);

			array_push($i['interactives'], $temp);

		}

		if ($counter == 0) {

			$b = array($sender->button('/alamat_create', 'Buat Alamat Baru'), $sender->button('/menu', 'Kembali ke Menu Utama.'));
			$io['interactive'] = $sender->interactive(null, null, "Anda belum mempunyai alamat yang tersimpan.\nSilakan menambahkan alamat baru.", $b, null);
			$i = $io;

			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
			return;

		}

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], "Silakan pilih alamat tujuan pengiriman.");

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);

    	$this->session->setLastSession('buy_alamat');
    	return;
	}

	public function instantBuy() {

		$this->session->setLastSession('buy_quantity_instant');
		$this->sendQuantity();
		return;

	}

	public function startBuy($thread_id) {

		$this->load->model('buy_model');
		$buy = $this->buy_model->find_buy($this->session->content['user']->username);
		
		if (empty($buy)) {

    		$this->buy_model->create_buy(['user' => $this->session->content['user']->username]);
    	}

    	$this->buy_model->update_buy($this->session->content['user']->username, ['thread_id' => $thread_id]);

		$thread_type = $this->checkThreadType($thread_id);

		if ($thread_type == 'instant') {

			$this->instantBuy();
			return;
		}

		if ($thread_type == 'normal') {

			$this->normalBuy();
			return;
		}

		return;
	}

	public function checkThreadType($thread_id) {

		$response = $this->get('v1/lapak/' . $thread_id, []);
		if (! $response['success']) return 'forbidden';
		$response = $response['result'];

		if (isset($response['thread']['is_instant_purchase'])) {

			if ($response['thread']['is_instant_purchase']) {

				return 'instant';
			}
		}

		return 'normal';
	}


}