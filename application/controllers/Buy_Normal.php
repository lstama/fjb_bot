<?php

include_once 'Buy.php';

class Buy_Normal extends Buy {


	public function normalBuy() {

		$response = $this->get('v1/fjb/location/addresses');
		if (!$response->isSuccess()) return;
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
			return;

		}

		$this->session->sendMessage("Silakan pilih alamat tujuan pengiriman.");
		$this->session->sendMultipleInteractiveMessage($multiple_interactive);
		$this->session->setLastSession('buy_normal_alamat');
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
				$this->selectQuantity();
				break;

			case 'shipping':
				$this->selectShippingAgent();
				break;

			case 'confirmation':
				$this->selectConfirmation();
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
				'address_id' => $result['id']
			];
			$this->session->buy_model->update_buy($this->session->username, $alamat);

			$this->session->setLastSession('buy_normal_quantity');
			$this->sendQuantity();
			return;
		}

		$this->session->sendReply('Alamat tidak valid.');

		$this->normalBuy();
		return;
	}

	public function getAlamat($id) {

		$response = $this->get('v1/fjb/location/addresses');
		if (!$response->isSuccess()) return $response;
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

	public function selectQuantity() {

		$result = $this->checkQuantity();
		if ($result == 'failed') return;

		$this->session->setLastSession('buy_normal_shipping');
		$this->sendShipping();
		return;
	}

	public function sendShipping() {

		$response = $this->getShippingCost();
		if (!$response->isSuccess()) return;
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

	private function getShippingCost() {

		$buy = $this->session->buy_model->find_buy($this->session->username);

		$query = [
			'query' => [
				'thread_id' => $buy['thread_id'],
				'dest_id' => $buy['dest_id'],
				'quantity' => $buy['quantity']
			]
		];

		$response = $this->get('v1/fjb/lapak/' . $buy['thread_id'] . '/shipping_costs', $query);
		return $response;
	}

	public function selectShippingAgent() {

		$choice = $this->session->message;
		$response = $this->getShippingCost();
		if (!$response->isSuccess()) return;
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

			$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
			$title = "Metode pengiriman tidak valid.";
			$caption = 'Silakan pilih metode pengiriman yang valid atau kembali ke menu utama.';
			$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);
			$this->session->sendInteractiveMessage($interactive);
			$this->sendShipping();
			return;
		}

		$this->session->buy_model->update_buy($this->session->username, ['shipping_id' => $choice]);
		$this->session->setLastSession('buy_normal_confirmation');
		$this->sendNormalConfirmation($jenis_jasa);
	}

	public function sendNormalConfirmation($jasa) {

		$result = $this->session->buy_model->find_buy($this->session->username);

		$barang = $this->getBarang($result['thread_id']);
		$this->displayBarang($barang);

		$alamat = $this->getAlamat($result['address_id']);

		$this->displayAlamat($alamat);

		$this->displayJasaPengiriman($jasa);

		$price = $barang['thread']['discounted_price'];
		$total_price = $result['quantity'] * $price;
		$biaya = 'Total barang : ' . $result['quantity'];
		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);
		$biaya .= "\nBiaya pengiriman : " . $this->toRupiah($jasa['method']['cost']);
		$biaya .= "\nTotal biaya : " . $this->toRupiah($jasa['method']['cost'] + $total_price);

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

	public function displayAlamat(Request_Result $result) {

		$result = $result->getContent();

		$kecamatan = $this->getAreaName($result['area_id']);
		if (!$kecamatan->isSuccess()) return;
		$kecamatan = $kecamatan->getContent();

		$kota = $this->getCityName($result['city_id']);
		if (!$kota->isSuccess()) return;
		$kota = $kota->getContent();

		$provinsi = $this->getProvinceName($result['province_id']);
		if (!$provinsi->isSuccess()) return;
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

	public function selectConfirmation() {

		if ($this->session->message == 'ya') {

			$buy = $this->session->buy_model->find_buy($this->session->username);

			$parameter = [
				'buyer_name' => $buy['buyer_name'],
				'buyer_phone' => $buy['buyer_phone'],
				'quantity' => $buy['quantity'],
				'tips' => '0',
				'address_id' => $buy['address_id'],
				'shipping_agent' => $buy['shipping_id'],
				'insurance' => '0'
			];

			$response = $this->post('v1/fjb/lapak/' . $buy['thread_id'] . '/buy_now', $parameter);
			if (!$response->isSuccess()) return;
			$response = $response->getContent();

			$this->sendCheckoutUrl($response, $buy);
		}
	}
}