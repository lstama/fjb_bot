<?php

require 'vendor/autoload.php';
include_once 'Sender.php';

class Create_Alamat extends CI_Controller {

	public $session;

	public function __construct($sess) {

		$this->session = $sess;
		parent::__construct();
	}

	public function createCreateSession($last_session) {

		$last_session = explode('_', $last_session, 2);
		switch ($last_session[0]) {
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
		        $this->unrecognizedCommand();
		}
	}

	public function startCreate() {

		$this->load->model('create_alamat_model');
		$create = $this->create_alamat_model->find_create_alamat($this->session->content['user']->username);
		
		if (empty($create)) {

    		$this->create_alamat_model->create_create_alamat(['user' => $this->session->content['user']->username]);
    	}

    	$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		$sender = new Sender();
		if (count($response['data']) >= 10) {


			$this->session->setLastSession('menu');
			$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
			$i['interactive'] = $sender->interactive(null, "Jumlah Alamat Sudah Maksimum", null, $b, null);
		
			$sender->sendReply($i);
			return;
		}

    	$this->session->setLastSession('alamat_create_label');
		
		$sender->sendReply('Silakan masukkan label alamat.');
	}

	public function createLabel() {

		$label = $this->session->content['message'];
		$data = ['label' => $label];
		$this->load->model('create_alamat_model');
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke nama
		$this->session->setLastSession('alamat_create_nama');
		$sender = new Sender();
		$sender->sendReply('Silakan masukkan nama tujuan pengiriman.');
	}

	public function createNama() {

		$nama = $this->session->content['message'];
		$data = ['nama' => $nama];
		$this->load->model('create_alamat_model');
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke telp
		$this->session->setLastSession('alamat_create_telp');
		$sender = new Sender();
		$sender->sendReply('Silakan masukkan nomor handphone tujuan pengiriman.');
	}

	public function createAlamat() {

		$alamat = $this->session->content['message'];
		$data = ['alamat' => $alamat];
		$this->load->model('create_alamat_model');
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke telp
		$this->session->setLastSession('alamat_create_confirmation');
		$sender = new Sender();
		$this->sendCreateConfirmation();
	}

	public function createConfirmation() {

		$confirmation = $this->session->content['message'];
		if ($confirmation != 'ya') {

			$this->sendCreateConfirmation();
			return;
		}


		$this->load->model('create_alamat_model');
		$temp = $this->create_alamat_model->find_create_alamat($this->session->content['user']->username);
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
		#ke telp
		#var_dump($result);
		#return;
		$this->session->setLastSession('alamat_daftar');
		$sender = new Sender();
		$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, "Alamat Berhasil Disimpan", null, $b, null);
		
		$sender->sendReply($i);
	}

	public function sendCreateConfirmation() {

		$this->load->model('create_alamat_model');
		$temp = $this->create_alamat_model->find_create_alamat($this->session->content['user']->username);
		$sender = new Sender;

		$kecamatan = $this->getArea($temp['kecamatan']);
		if (! $kecamatan['success']) return;
		$kecamatan = $kecamatan['result'];
		#get kota
		$kota = $this->getCity($temp['kota']);
		if (! $kota['success']) return;
		$kota = $kota['result'];
		#get provinsi
		$provinsi = $this->getProvince($temp['provinsi']);
		if (! $provinsi['success']) return;
		$provinsi = $provinsi['result'];

		$k = "Konfirmasi\nLabel Alamat : " . $temp['label'] . "\nNama : " . $temp['nama'] . "\nNomor Handphone : " . $temp['telp'] . "\nProvinsi : " . $provinsi . "\nKota/Kabupaten : " . $kota . "\nKecamatan : " . $kecamatan . "\nAlamat : " . $temp['alamat'];

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $k);
		
		$b = array($sender->button('ya', 'Ya'),$sender->button('/alamat_daftar', 'Tidak'));
		$i['interactive'] = $sender->interactive(null, 'Apakah data di atas sudah benar?', "Kaskus tidak bertanggung jawab atas penyimpanan alamat yang salah.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
	}

	public function createTelp() {

		$telp = $this->session->content['message'];

		$this->load->model('create_alamat_model');
		$sender = new Sender();

		if ((! is_numeric($telp)) or (count($telp) > 13)) {

			#not valid
			$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
			$i['interactive'] = $sender->interactive(null, 'Nomor Tidak Valid', "Silakan masukkan nomor yang valid atau kembali ke menu utama.", $b, null);
		
			$sender->sendReply($i);
			return;
		}

		$data = ['telp' => $telp];
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke provinsi
		$this->session->setLastSession('alamat_create_provinsi');
		$this->sendProvinceList();
	}

	public function sendProvinceList() {

		#TODO;
		$response = $this->get('v1/fjb/location/provinces');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], 'Silakan pilih provinsi lokasi tujuan.');
		
		$counter = 0; #maximum counter = 10
		$button_counter = 0;
		$i['interactives'] = [];
		$b = [];

		foreach ($response['data'] as $a) {

			$counter += 1;
			$button_counter +=1;
			array_push($b, $sender->button($a['id'], $a['name']));
			
			if (($button_counter == 5) or ($counter == count($response['data'])) ) {

				$temp = $sender->interactive(null, null, null, $b, null);
				array_push($i['interactives'], $temp);
				$b = [];	
				$button_counter = 0;
			}
		}

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
	}

	public function createProvinsi() {

		$provinsi = $this->session->content['message'];

		$this->load->model('create_alamat_model');
		$sender = new Sender();

		if ((! is_numeric($provinsi)) or ($provinsi < 1) or ($provinsi > 34)) {

			#not valid
			$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
			$i['interactive'] = $sender->interactive(null, 'Provinsi Tidak Valid', "Silakan pilih provinsi yang valid atau kembali ke menu utama.", $b, null);
		
			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
			$this->sendProvinceList();
			return;
		}

		$data = ['provinsi' => $provinsi];
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke provinsi
		$this->session->setLastSession('alamat_create_kota');
		$this->sendCityList($provinsi);
	}

	public function sendCityList($provinsi) {

		#TODO;
		$response = $this->get('v1/fjb/location/provinces/' . $provinsi . '/cities');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], 'Silakan pilih kabupaten/kota lokasi tujuan.');
		
		$counter = 0; #maximum counter = 10
		$button_counter = 0;
		$i['interactives'] = [];
		$b = [];

		foreach ($response['data'] as $a) {

			$counter += 1;
			$button_counter +=1;
			array_push($b, $sender->button($a['id'], $a['name']));
			
			if (($button_counter == 5) or ($counter == count($response['data'])) ) {

				$temp = $sender->interactive(null, null, null, $b, null);
				array_push($i['interactives'], $temp);
				$b = [];	
				$button_counter = 0;
			}
		}

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
	}

	public function checkCity($province, $city) {

		$response = $this->get('v1/fjb/location/provinces/' . $province . '/cities');
		if (! $response['success']) return;
		$response = $response['result'];

		$tidak_ada = true;
		foreach ($response['data'] as $a) {

			if ($a['id'] == $city) $tidak_ada = false;
		}

		return $tidak_ada;
	}

	public function createKota() {

		$kota = $this->session->content['message'];

		$this->load->model('create_alamat_model');
		$provinsi = $this->create_alamat_model->find_create_alamat($this->session->content['user']->username);
		$provinsi = $provinsi['provinsi'];
		$sender = new Sender();

		if ((! is_numeric($kota)) or $this->checkCity($provinsi, $kota)) {

			#not valid
			$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
			$i['interactive'] = $sender->interactive(null, 'Kota Tidak Valid', "Silakan pilih kota yang valid atau kembali ke menu utama.", $b, null);
		
			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
			$this->sendCityList($provinsi);
			return;
		}

		$data = ['kota' => $kota];
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke provinsi
		$this->session->setLastSession('alamat_create_kecamatan');
		$this->sendAreaList($kota);
	}

	public function sendAreaList($kota) {

		#TODO;
		$response = $this->get('v1/fjb/location/cities/' . $kota . '/areas');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], 'Silakan pilih kecamatan lokasi tujuan.');
		
		$counter = 0; #maximum counter = 10
		$button_counter = 0;
		$i['interactives'] = [];
		$b = [];

		foreach ($response['data'] as $a) {

			$counter += 1;
			$button_counter +=1;
			array_push($b, $sender->button($a['id'], $a['name']));
			
			if (($button_counter == 5) or ($counter == count($response['data'])) ) {

				$temp = $sender->interactive(null, null, null, $b, null);
				array_push($i['interactives'], $temp);
				$b = [];	
				$button_counter = 0;
			}
		}

		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
	}

	public function checkArea($city, $area) {

		$response = $this->get('v1/fjb/location/cities/' . $city . '/areas');
		if (! $response['success']) return;
		$response = $response['result'];

		$tidak_ada = true;
		foreach ($response['data'] as $a) {

			if ($a['id'] == $area) $tidak_ada = false;
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
			$this->sendAreaList($kota);
			return;
		}

		$data = ['kecamatan' => $kecamatan];
		$this->create_alamat_model->update_create_alamat($this->session->content['user']->username, $data);
		
		#ke alamat
		$this->session->setLastSession('alamat_create_alamat');
		$sender->sendReply('Silakan masukkan alamat tujuan (jalan, desa, kelurahan, dsb).');
	}

	public function get($parameter) {

		try {

    		$response = $this->session->oauth_client->get($parameter);
			$temp = $response->json();
    	}
    	catch (\Kaskus\Exceptions\KaskusRequestException $exception) {
 	  		// Kaskus Api returned an error
    		$response =  $exception->getMessage();
		} 
		catch (\Exception $exception) {
    		// some other error occured
    		$response =  $exception->getMessage();
		}

		#error occured
		if ( (gettype($response) == 'string') or (isset($temp) == FALSE) ) {

			$this->errorOccured();
			echo $response;
			return ['success' => false, 'result' => ''];
		}

		return ['success' => true, 'result' => $temp];
	}

	public function post($url, $parameter) {

		try {

    		$response = $this->session->oauth_client->post($url,['body' => $parameter]);
			$temp = $response->json();
    	}
    	catch (\Kaskus\Exceptions\KaskusRequestException $exception) {
 	  		// Kaskus Api returned an error
    		$response =  $exception->getMessage();
		} 
		catch (\Exception $exception) {
    		// some other error occured
    		$response =  $exception->getMessage();
		}

		#error occured
		if ( (gettype($response) == 'string') or (isset($temp) == FALSE) ) {

			$this->errorOccured();
			echo $response;
			return ['success' => false, 'result' => ''];
		}

		return ['success' => true, 'result' => $temp];
	}

	public function getProvince($id) {

		$response = $this->get('v1/fjb/location/provinces/' . $id);
		if (! $response['success']) return ['success' => false, 'result' => ''];

		return ['success' => true, 'result' => $response['result']['name']];

	}

	public function getCity($id) {

		$response = $this->get('v1/fjb/location/cities/' . $id);
		if (! $response['success']) return ['success' => false, 'result' => ''];

		return ['success' => true, 'result' => $response['result']['name']];

	}

	public function getArea($id) {

		$response = $this->get('v1/fjb/location/areas/' . $id);
		if (! $response['success']) return ['success' => false, 'result' => ''];

		return ['success' => true, 'result' => $response['result']['name']];

	}

	public function unrecognizedCommand() {

		$this->session->setLastSession('unrecognizedCommand');

		$sender = new Sender();
		$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, "Perintah Tidak Dikenal", "Silakan masukkan perintah yang benar atau kembali ke menu utama.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		return;		
	}

	public function errorOccured() {

		$this->session->setLastSession('errorOccured');

		$sender = new Sender();
		$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, "Terjadi Kesalahan pada Server", "Silakan kembali ke menu utama.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		return;
	}
}