<?php

require 'vendor/autoload.php';
include_once 'Sender.php';
include_once 'Create_Alamat.php';

class Alamat extends CI_Controller {

	public $session;

	public function __construct($sess) {

		$this->session = $sess;
		parent::__construct();
	}

	public function main($command) {

		$command = explode('_', $command, 2);

		switch ($command[0]) {
		    case 'daftar':
		        $this->daftarAlamat();
		        break;
		    case 'lihat':
		        $this->lihatAlamat($command[1]);
		        break;
		    case 'create':
		    	$create = new Create_Alamat($this->session);
		        $create->startCreate();
		        break;
		    case 'hapus':
		    	$this->sendDeleteConfirmation($command[1]);
		        break;
		    case 'default':
		    	$this->setToDefault($command[1]);
		        break;

		    default:
		        $this->unrecognizedCommand();
		}

	}

	public function lastSessionSpecific($last_session) {

		$last_session = explode('_', $last_session, 2);
		switch ($last_session[0]) {
		    case 'create':
		        $create = new Create_Alamat($this->session);
		        $create->createCreateSession($last_session[1]);
		        break;

		    case 'delete':
		        $this->deleteAlamat($last_session[1]);
		        break;
		    default:
		        $this->unrecognizedCommand();
		}
	}

	public function daftarAlamat() {

		$this->session->setLastSession('alamat_daftar');
		$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();

		$counter = 0; #maximum counter = 10
		$i['interactives'] = [];
		foreach ($response['data'] as $a) {

			$counter += 1;
			if ($counter == 11) break;

			$n = $a['name'];
			$b = array($sender->button('/alamat_lihat_' . $a['id'], 'Detail'), $sender->button('/alamat_default_' . $a['id'], 'Jadikan Alamat Utama'), $sender->button('/alamat_hapus_' . $a['id'], 'Hapus'));

			if ($a['default']) {

				$b = array($sender->button('/alamat_lihat_' . $a['id'], 'Detail'));
				$n .= '(Alamat Utama)';
			}
			$temp = $sender->interactive(null, $n, $a['address'], $b, null);

			array_push($i['interactives'], $temp);

		}

		if ($counter == 0) {

			$b = array($sender->button('/alamat_create', 'Buat Alamat Baru'), $sender->button('/menu', 'Kembali ke Menu Utama.'));
			$io['interactive'] = $sender->interactive(null, null, "Anda belum mempunyai alamat yang tersimpan.\nSilakan menambahkan alamat baru.", $b, null);
			$i = $io;
		}
		$sender->sendReply($i);
		return;
	}

	public function lihatAlamat($id) {

		$this->session->setLastSession('alamat_lihat');
		$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();

		$ada = false;
		$result = [];
		foreach ($response['data'] as $k => $a) {

			if ($a['id'] == $id) {

				$ada = true;
				$result = $a;
				break;
			}

		}

		if (!$ada) {

			$sender->sendReply('Alamat tidak ditemukan.');
			return;
		}

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
		$b = array($sender->button('/alamat_daftar', 'Kembali ke Daftar Alamat'),$sender->button('/menu', 'Kembali ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, null, null, $b, null);
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		return;
	}

	public function sendDeleteConfirmation($id) {

		$this->session->setLastSession('alamat_delete_confirmation');
		$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();

		$ada = false;
		$result = [];
		foreach ($response['data'] as $k => $a) {

			if ($a['id'] == $id) {

				$ada = true;
				$result = $a;
				break;
			}
		}

		if (!$ada) {

			$sender->sendReply('Alamat tidak ditemukan.');
			return;
		}

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

		
		$text = $result['owner_name'] . "\n" . $result['address'] . "\n" . $kecamatan . ", Kota/Kab " . $kota . "\n" . $provinsi . "\nTelephone/Handphone: " . $result['owner_phone'];
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $text);

		$b = array($sender->button('ya', 'Ya'),$sender->button('/alamat_daftar', 'Tidak'));
		$i['interactive'] = $sender->interactive(null, $result['name'], 'Apakah anda yakin ingin menghapus alamat ini?',$b,null);
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		$this->session->setLastSession('alamat_delete_' . $id);


	}

	public function deleteAlamat($id) {

		$confirmation = $this->session->content['message'];
		if ($confirmation != 'ya') {

			$this->sendDeleteConfirmation($id);
			return;
		}

		$result = $this->delete('v1/fjb/location/addresses/' . $id);
		#ke telp
		#var_dump($result);
		#return;
		$this->session->setLastSession('alamat_daftar');
		$sender = new Sender();
		$b = array($sender->button('/alamat_daftar', 'Kembali ke Daftar Alamat'),$sender->button('/menu', 'Kembali ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, "Alamat Berhasil Dihapus", null, $b, null);
		
		$sender->sendReply($i);
	}

	public function setToDefault($id) {

		$this->session->setLastSession('alamat_default');
		$parameter = [
						'default' => 'true'
					];

		$result = $this->post('v1/fjb/location/addresses/' . $id, $parameter);
		#ke telp
		#var_dump($result);
		#return;
		$this->session->setLastSession('alamat_default');
		$sender = new Sender();
		$b = array($sender->button('/alamat_daftar', 'Kembali ke Daftar Alamat'),$sender->button('/menu', 'Kembali ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, "Alamat Utama Berhasil Diubah", null, $b, null);
		
		$sender->sendReply($i);
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

	public function delete($url, $parameter = null) {

		try {

    		$response = $this->session->oauth_client->delete($url,['body' => $parameter]);
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