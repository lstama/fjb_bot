<?php 

require 'vendor/autoload.php';
include_once 'Sender.php';
include_once 'Alamat.php';

class FJB_Bot extends CI_Controller {

	public $session;

	public function __construct($sess) {
		
		$this->session = $sess;
	}

	public function main() {

		$command = explode('_', $this->session->content['message'], 2);

		switch ($command[0]) {
		    case '/menu':
		        $this->menu();
		        break;
		        
		    case '/alamat':
		        $alamat = new Alamat($this->session);
		        $alamat->main($command[1]);
		        break;

		    default:
		        $this->lastSessionSpecific();
		}
	}

	public function menu() {

		$this->session->setLastSession('menu');

		$sender = new Sender();
		$b = array($sender->button('/alamat_daftar', 'Daftar Alamat'));
		$i['interactive'] = $sender->interactive(null, "Menu Utama", "Silakan pilih menu di bawah untuk melanjutkan.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		return;

	}

	public function lastSessionSpecific() {

		$last_session = explode('_', $this->session->last_session, 2);
		switch ($last_session[0]) {
		    case 'alamat':
		        $alamat = new Alamat($this->session);
		        $alamat->lastSessionSpecific($last_session[1]);
		        break;

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

}