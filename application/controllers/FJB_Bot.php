<?php 

include_once 'Features.php';
include_once 'Alamat.php';

class FJB_Bot extends Features {

	public function main() {

		$message_prefix = $this->getPrefix($this->message_now);
		$message_suffix = $this->getSuffix($this->message_now);

		switch ($message_prefix) {

			case '/menu':

				$this->sendMenuDialog();
				break;

			case '/alamat':

				$alamat = new Alamat;
				$alamat->setMessageNow($message_suffix);
				$alamat->setSessionNow($this->session_now);
				$alamat->setSession($this->session);
				$alamat->main();
				break;

		    default:

		    	$this->lastSessionSpecific();
		}
	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			case 'alamat':

				$alamat = new Alamat;
				$alamat->setMessageNow($this->message_now);
				$alamat->setSessionNow($session_suffix);
				$alamat->setSession($this->session);
				$alamat->lastSessionSpecific();
				break;

		    default:

		    	$this->sendUnrecognizedCommandDialog();
		}
	}

	public function sendMenuDialog() {

		$this->session->setLastSession('menu');

		$buttons = [
			$this->session->createButton('/alamat_daftar', 'Daftar Alamat'),
			$this->session->createButton('/alamat_create', 'Buat Alamat Baru'),
			$this->session->createButton('/lapak_start', 'Cari Barang')
			];
		$title	 	 = "Menu Utama";
		$caption 	 = "Silakan pilih menu di bawah untuk melanjutkan.";
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);

		$this->session->sendInteractiveMessage($interactive);
	}
}