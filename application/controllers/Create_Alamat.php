<?php

include_once 'FJB.php';

class Create_Alamat extends FJB {

	public function startCreate() {

		$create = $this->session->create_alamat_model->find_create_alamat($this->session->username);

		if (empty($create)) {

			$this->session->create_alamat_model->create_create_alamat(['user' => $this->session->username]);
		}

		$response = $this->get('v1/fjb/location/addresses');
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

		if (count($response['data']) >= 10) {

			$this->sendAlamatFullDialog();
			return;
		}

		$this->session->setLastSession('alamat_create_label');

		$this->session->sendReply('Silakan masukkan label alamat.');
	}

	private function sendAlamatFullDialog() {

		$this->session->setLastSession('menu');

		$buttons	 = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
		$title 		 = "Jumlah Alamat Sudah Maksimum";
		$interactive = $this->session->createInteractive(null, $title, null, $buttons, null);

		$this->session->sendInteractiveReply($interactive);
	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			case 'label':

				$this->createLabel();
				break;

			case 'nama':

				$this->createNama();
				break;

			case 'telp':

				$this->createTelp();
				break;

			case 'provinsi':

				$this->createProvinsi();
				break;

			case 'kota':

				$this->createKota();
				break;

			case 'kecamatan':

				$this->createKecamatan();
				break;

			case 'alamat':

				$this->createAlamat();
				break;

			case 'confirmation':

				$this->createConfirmation();
				break;

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	private function createLabel() {

		$label = $this->session->message;
		$data = ['label' => $label];
		$this->session->create_alamat_model->update_create_alamat($this->session->username, $data);

		$this->session->setLastSession('alamat_create_nama');

		$this->session->sendReply('Silakan masukkan nama penerima kiriman.');
	}

	private function createNama() {

		$nama = $this->session->message;
		$data = ['nama' => $nama];
		$this->session->create_alamat_model->update_create_alamat($this->session->username, $data);

		$this->session->setLastSession('alamat_create_telp');

		$this->session->sendReply('Silakan masukkan nomor handphone tujuan pengiriman.');
	}

	private function createTelp() {

		$telp = $this->session->message;

		if ((!is_numeric($telp)) or (strlen($telp) > 13)) {

			$buttons	 = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
			$title		 = 'Nomor Tidak Valid';
			$caption	 = "Silakan masukkan nomor yang valid atau kembali ke menu utama.";
			$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

			$this->session->sendInteractiveMessage($interactive);
			return;
		}

		$data = ['telp' => $telp];
		$this->session->create_alamat_model->update_create_alamat($this->session->username, $data);

		$this->session->setLastSession('alamat_create_provinsi');
		$this->sendProvinceList();
	}

	private function sendProvinceList() {

		$response = $this->get('v1/fjb/location/provinces');
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

		$this->session->sendMessage('Silakan pilih provinsi lokasi tujuan.');

		$counter = 0; #maximum counter = 50
		$button_counter = 0; #maximum button counter = 5
		$multiple_interactive = [];
		$buttons = [];

		foreach ($response['data'] as $provinsi) {

			$counter += 1;
			$button_counter +=1;
			array_push($buttons, $this->session->createButton($provinsi['id'], $provinsi['name']));

			if (($button_counter == 5) or ($counter == count($response['data'])) ) {

				$temp = $this->session->createInteractive(null, null, null, $buttons);
				array_push($multiple_interactive, $temp);
				$buttons = [];
				$button_counter = 0;
			}
		}

		$this->session->sendMultipleInteractiveMessage($multiple_interactive);
	}
	private function createProvinsi() {

		$provinsi = $this->session->message;

		if ((!is_numeric($provinsi)) or ($provinsi < 1) or ($provinsi > 34)) {

			$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
			$title = 'Provinsi Tidak Valid';
			$caption = "Silakan pilih provinsi yang valid atau kembali ke menu utama.";
			$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

			$this->session->sendInteractiveMessage($interactive);
			$this->sendProvinceList();
			return;
		}

		$data = ['provinsi' => $provinsi];
		$this->session->create_alamat_model->update_create_alamat($this->session->username, $data);

		$this->session->setLastSession('alamat_create_kota');
		$this->sendCityList($provinsi);
	}

	private function sendCityList($provinsi) {
		
		$response = $this->get('v1/fjb/location/provinces/' . $provinsi . '/cities');
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

		$this->session->sendMessage('Silakan pilih kabupaten/kota lokasi tujuan.');

		$counter = 0;
		$button_counter = 0;
		$multiple_interactive = [];
		$buttons = [];

		foreach ($response['data'] as $kota) {

			$counter += 1;
			$button_counter +=1;

			array_push($buttons, $this->session->createButton($kota['id'], $kota['name']));

			if (($button_counter == 5) or ($counter == count($response['data'])) ) {

				$temp = $this->session->createInteractive(null, null, null, $buttons);
				array_push($multiple_interactive, $temp);
				$buttons = [];
				$button_counter = 0;
			}
		}

		$this->session->sendMultipleInteractiveMessage($multiple_interactive);
	}


	private function createKota() {

		$kota = $this->session->message;

		$provinsi = $this->session->create_alamat_model->find_create_alamat($this->session->username);
		$provinsi = $provinsi['provinsi'];

		if ((! is_numeric($kota)) or $this->isCityNotExist($provinsi, $kota)) {

			$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
			$title = 'Kota Tidak Valid';
			$caption = "Silakan pilih kota yang valid atau kembali ke menu utama.";
			$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

			$this->session->sendInteractiveMessage($interactive);
			$this->sendCityList($provinsi);
			return;
		}

		$data = ['kota' => $kota];
		$this->session->create_alamat_model->update_create_alamat($this->session->username, $data);

		#ke provinsi
		$this->session->setLastSession('alamat_create_kecamatan');
		$this->sendAreaList($kota);
	}

	private function isCityNotExist($province, $city) {

		$response = $this->get('v1/fjb/location/provinces/' . $province . '/cities');
		if (! $response->isSuccess()) return true;
		$response = $response->getContent();

		$not_exist = true;
		foreach ($response['data'] as $kota) {

			if ($kota['id'] == $city) $not_exist = false;
		}

		return $not_exist;
	}

	private function sendAreaList($kota) {

		$response = $this->get('v1/fjb/location/cities/' . $kota . '/areas');
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

		$this->session->sendMessage('Silakan pilih kecamatan lokasi tujuan.');

		$counter = 0;
		$button_counter = 0;
		$multiple_interactive = [];
		$buttons = [];

		foreach ($response['data'] as $kecamatan) {

			$counter += 1;
			$button_counter +=1;
			array_push($buttons, $this->session->createButton($kecamatan['id'], $kecamatan['name']));

			if (($button_counter == 5) or ($counter == count($response['data'])) ) {

				$temp = $this->session->createInteractive(null, null, null, $buttons);
				array_push($multiple_interactive, $temp);
				$buttons = [];
				$button_counter = 0;
			}
		}

		$this->session->sendMultipleInteractiveMessage($multiple_interactive);
	}

	private function createKecamatan() {

		$kecamatan = $this->session->message;

		$kota = $this->session->create_alamat_model->find_create_alamat($this->session->username);
		$kota = $kota['kota'];

		if ((! is_numeric($kecamatan)) or $this->isAreaNotExist($kota, $kecamatan)) {

			$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
			$title = 'Kecamatan Tidak Valid';
			$caption = "Silakan pilih kecamatan yang valid atau kembali ke menu utama.";
			$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

			$this->session->sendInteractiveMessage($interactive);
			$this->sendAreaList($kota);
			return;
		}

		$data = ['kecamatan' => $kecamatan];
		$this->session->create_alamat_model->update_create_alamat($this->session->username, $data);

		$this->session->setLastSession('alamat_create_alamat');
		$this->session->sendReply('Silakan masukkan alamat tujuan (jalan, desa, kelurahan, dsb).');
	}

	private function isAreaNotExist($city, $area) {

		$response = $this->get('v1/fjb/location/cities/' . $city . '/areas');
		if (! $response->isSuccess()) return true;
		$response = $response->getContent();

		$not_exist = true;
		foreach ($response['data'] as $kecamatan) {

			if ($kecamatan['id'] == $area) $not_exist = false;
		}

		return $not_exist;
	}

	private function createAlamat() {

		$alamat = $this->session->message;
		$data = ['alamat' => $alamat];
		$this->session->create_alamat_model->update_create_alamat($this->session->username, $data);

		$this->session->setLastSession('alamat_create_confirmation');
		$this->sendCreateConfirmation();
	}

	private function sendCreateConfirmation() {

		$temp = $this->session->create_alamat_model->find_create_alamat($this->session->username);

		$kecamatan = $this->getAreaName($temp['kecamatan']);
		if (! $kecamatan->isSuccess()) return;
		$kecamatan = $kecamatan->getContent();

		$kota = $this->getCityName($temp['kota']);
		if (! $kota->isSuccess()) return;
		$kota = $kota->getContent();

		$provinsi = $this->getProvinceName($temp['provinsi']);
		if (! $provinsi->isSuccess()) return;
		$provinsi = $provinsi->getContent();

		$text = "Label Alamat : " . $temp['label'] .
			"\nNama : " . $temp['nama'] .
			"\nNomor Handphone : " . $temp['telp'] .
			"\nProvinsi : " . $provinsi .
			"\nKota/Kabupaten : " . $kota .
			"\nKecamatan : " . $kecamatan .
			"\nAlamat : " . $temp['alamat'];

		$this->session->sendMessage($text);

		$buttons = [
			$this->session->createButton('ya', 'Ya'),
			$this->session->createButton('/alamat_daftar', 'Tidak')
		];
		$title = 'Apakah data di atas sudah benar?';
		$caption = "Kaskus tidak bertanggung jawab atas penyimpanan alamat yang salah.";
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

		$this->session->sendInteractiveMessage($interactive);
	}

	private function createConfirmation() {

		$confirmation = $this->session->message;
		if ($confirmation != 'ya') {

			$this->sendCreateConfirmation();
			return;
		}

		$temp = $this->session->create_alamat_model->find_create_alamat($this->session->username);
		$parameter = [
			'title' => $temp['label'],
			'owner_name' => $temp['nama'],
			'owner_phone' => $temp['telp'],
			'default' => false,
			'province_id' => $temp['provinsi'],
			'city_id' => $temp['kota'],
			'area_id' => $temp['kecamatan'],
			'address' => $temp['alamat']
		];

		$result = $this->post('v1/fjb/location/addresses', $parameter);

		$this->session->setLastSession('alamat_daftar');
		$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
		$title = "Alamat Berhasil Disimpan";
		$interactive = $this->session->createInteractive(null, $title, null, $buttons);

		$this->session->sendInteractiveMessage($interactive);
	}
}