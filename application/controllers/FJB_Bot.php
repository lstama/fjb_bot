<?php 

require 'vendor/autoload.php';
include_once 'Sender.php';

class FJB_Bot extends CI_Controller {

	public $session;

	public function __construct($sess) {
		
		$this->session = $sess;
		$this->main();
	}

	public function main() {

		$command = explode(' ', $this->session->content['message'], 2);

		switch ($command[0]) {
		    case '/menu':
		        $this->menu();
		        break;
		    case '/daftar_alamat':
		        $this->daftarAlamat();
		        break;
		    case '/lihat_alamat':
		        $this->lihatAlamat($command[1]);
		        break;
		    // case label3:
		    //     code to be executed if n=label3;
		    //     break;
		    // ...
		    default:
		        $this->lastSessionSpecific();
		}
	}

	public function menu() {

		$this->session->setLastSession('menu');

		$sender = new Sender();
		$b = array($sender->button('/daftar_alamat', 'Daftar Alamat'));
		$i['interactive'] = $sender->interactive(null, "Menu Utama", "Silakan pilih menu di bawah untuk melanjutkan.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		return;

	}

	public function lastSessionSpecific() {

		switch ($this->session->last_session) {
		    //case 'menu':
		    //    $this->menu();
		    //    break;
		    // case label2:
		    //     code to be executed if n=label2;
		    //     break;
		    // case label3:
		    //     code to be executed if n=label3;
		    //     break;
		    // ...
		    default:
		        $this->unrecognizedCommand();
		}
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

	public function daftarAlamat() {

		$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();

		//var_dump($response);
		$counter = 0; #maximum counter = 10
		$i['interactives'] = [];
		foreach ($response['data'] as $a) {

			//var_dump($a);
			$counter += 1;
			if ($counter == 11) break;

			$b = array($sender->button('/lihat_alamat ' . $a['id'], 'Detail'));
			$temp = $sender->interactive(null, $a['name'], $a['address'], $b, null);

			array_push($i['interactives'], $temp);

		}

		if ($counter == 0) $i = "Anda belum mempunyai alamat yang tersimpan.\nSilakan menambahkan alamat baru.";
		//$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		$sender->sendReply($i);
		return;
	}

	public function lihatAlamat($id) {

		$response = $this->get('v1/fjb/location/addresses');
		if (! $response['success']) return;
		$response = $response['result'];

		#Retrieve success
		$sender = new Sender();

		//var_dump($response);
		$no_alamat = -1;
		$result = [];
		foreach ($response['data'] as $k => $a) {

			if ($a['id'] == $id) {

				$no_alamat = $k;
				$result = $a;
				break;
			}

		}

		if ($no_alamat == -1) {

			$sender->sendReply('Alamat tidak ditemukan.');
			return;
		}

		#get kecamatan
		#echo $result['area_id'];
		$kecamatan = $this->getArea($result['area_id']);
		if (! $kecamatan['success']) return;
		$kecamatan = $kecamatan['result'];
		#echo 'here';
		#get kota
		$kota = $this->getCity($result['city_id']);
		if (! $kota['success']) return;
		$kota = $kota['result'];

		#get provinsi
		$provinsi = $this->getProvince($result['province_id']);
		if (! $provinsi['success']) return;
		$provinsi = $provinsi['result'];

		$i[interactive] = $sender->interactive(null, $result['name'], $result['owner_name'],null,null);
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		$text = $result['address'] . "\n" . $kecamatan . ", Kota/Kab " . $kota . "\n" . $provinsi . "\nTelephone/Handphone: " . $result['owner_phone'];
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $text);
		//$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		return;
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
}