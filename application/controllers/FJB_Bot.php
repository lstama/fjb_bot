<?php 

require 'vendor/autoload.php';

class FJB_Bot extends CI_Controller {

	public $session;



	public function getPrefix() {

		$command = explode('_', $this->session->message, 2);
		return $command[0];
	}

	public function getSuffix() {

		$command = explode('_', $this->session->message, 2);
		return $command[1];
	}

	public function main() {

		$prefix = $this->getPrefix();
		$suffix = $this->getSuffix();

		switch ($prefix) {
		    case '/menu':
		        $this->sendMenu();
		        break;
		        
		    case '/alamat':
		        $alamat = new Alamat;
		        $alamat->main($command[1]);
		        break;

		    case '/lapak':
		        $lapak = new Lapak($this->session);
		        $lapak->main($command[1]);
		        break;

		    case '/buy':
		        $buy = new Buy($this->session);
		        $buy->startBuy($command[1]);
		        break;

		    default:
		        $this->lastSessionSpecific();
		}
	}

	public function sendMenu() {

		$this->session->setLastSession('menu');

		$sender = new Sender();
		$b = array($sender->button('/alamat_daftar', 'Daftar Alamat'), $sender->button('/alamat_create', 'Buat Alamat Baru'), $sender->button('/lapak_start', 'Cari Barang'));
		$i['interactive'] = $sender->interactive(null, "Menu Utama", "Silakan pilih menu di bawah untuk melanjutkan.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['User_Account'], $i);
		return;

	}

	public function lastSessionSpecific() {

		$last_session = explode('_', $this->session->last_session, 2);
		switch ($last_session[0]) {
		    case 'alamat':
		        $alamat = new Alamat($this->session);
		        $alamat->lastSessionSpecific($last_session[1]);
		        break;

		    case 'lapak':
		        $lapak = new Lapak($this->session);
		        $lapak->lastSessionSpecific($last_session[1]);
		        break;

		    case 'buy':
		        $buy = new Buy($this->session);
		        $buy->createBuySession($last_session[1]);
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
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['User_Account'], $i);
		return;		
	}

	public function errorOccured() {

		$this->session->setLastSession('errorOccured');

		$sender = new Sender();
		$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, "Terjadi Kesalahan pada Server", "Silakan kembali ke menu utama.", $b, null);
		
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['User_Account'], $i);
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