<?php

public function checkArea($city, $area) {

		$response = $this->get('v1/fjb/location/cities/' . $city . '/areas');
		if (! $response['success']) return;
		$response = $response['result'];

		$tidak_ada = true;
		foreach ($response['data'] as $a) {

			if ($a['id'] == $city) $tidak_ada = false;
		}

		return $tidak_ada;
	}

	public function createKecamatan() {

		$kecamatan = $this->session->content['message'];

		$this->load->model('create_alamat_model');
		$kota = $this->create_alamat_model->find_create_alamat($this->session->content['user']->username);
		$kota = $kota['kota'];
		$sender = new Sender();

		if ((! is_numeric($kecamatan)) or $this->checkArea($kota, $kecamatan)) {

			#not valid
			$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
			$i['interactive'] = $sender->interactive(null, 'Kecamatan Tidak Valid', "Silakan pilih kecamatan yang valid atau kembali ke menu utama.", $b, null);
		
			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
			$this->sendCityList($provinsi);
			return;
		}

		$data = ['kecamatan' => $kecamatan];
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke alamat
		$this->session->setLastSession('alamat_create_alamat');
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], 'Silakan masukkan alamat tujuan (jalan, desa, kelurahan, dsb).');
	}