<?php

include_once 'FJB.php';
include_once 'Create_Alamat.php';

class Alamat extends FJB {

	public function main() {

		$message_prefix = $this->getPrefix($this->message_now);
		$message_suffix = $this->getSuffix($this->message_now);

		switch ($message_prefix) {

			case 'daftar':

				$this->sendDaftarAlamat();
				break;

			case 'lihat':

				$this->sendAlamatDetails($message_suffix);
				break;

			case 'default':

				$this->setToDefault($message_suffix);
				break;

			case 'hapus':

				$this->sendDeleteConfirmation($message_suffix);
				break;

			case 'create':

				$create = new Create_Alamat;
				$create->setMessageNow($message_suffix);
				$create->setSessionNow($this->session_now);
				$create->setSession($this->session);
				$create->startCreate();
				break;

			default:

				$this->lastSessionSpecific();
		}
	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			case 'delete':

				$this->deleteAlamat($session_suffix);
				break;

			case 'create':

				$create = new Create_Alamat;
				$create->setMessageNow($this->message_now);
				$create->setSessionNow($session_suffix);
				$create->setSession($this->session);
				$create->lastSessionSpecific();
				break;

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	private function sendDaftarAlamat() {

		$this->session->setLastSession('alamat_daftar');
		$response = $this->get('v1/fjb/location/addresses');
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$total_alamat = 0; #maximum counter = 10
		$multiple_interactive = [];
		foreach ($response['data'] as $alamat) {

			$total_alamat += 1;
			if ($total_alamat > 10) break;

			$interactive = $this->createSearchAlamatInteractive($alamat);

			array_push($multiple_interactive, $interactive);
		}

		if ($total_alamat == 0) {

			$this->sendEmptyAlamatDialog();
		} else {

			$this->session->sendMultipleInteractiveMessage($multiple_interactive);
		}
	}

	private function createSearchAlamatInteractive($alamat) {

		$name = $alamat['name'];
		$buttons = [
			$this->session->createButton('/alamat_lihat_' . $alamat['id'], 'Detail'),
			$this->session->createButton('/alamat_default_' . $alamat['id'], 'Jadikan Alamat Utama'),
			$this->session->createButton('/alamat_hapus_' . $alamat['id'], 'Hapus')
		];

		if ($alamat['default']) {

			$buttons = [$this->session->createButton('/alamat_lihat_' . $alamat['id'], 'Detail')];
			$name .= '(Alamat Utama)';
		}

		$interactive = $this->session->createInteractive(null, $name, $alamat['address'], $buttons);

		return $interactive;
	}

	private function sendAlamatDetails($id) {

		$this->session->setLastSession('alamat_lihat');
		$response = $this->get('v1/fjb/location/addresses');
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$alamat_exist = false;
		$alamat = [];
		foreach ($response['data'] as $a) {

			if ($a['id'] == $id) {

				$alamat_exist = true;
				$alamat = $a;
				break;
			}
		}

		if (!$alamat_exist) {

			$this->session->sendReply('Alamat tidak ditemukan.');
			return;
		}

		$kecamatan = $this->getAreaName($alamat['area_id']);
		if (!$kecamatan->isSuccess()) return;
		$kecamatan = $kecamatan->getContent();

		$kota = $this->getCityName($alamat['city_id']);
		if (!$kota->isSuccess()) return;
		$kota = $kota->getContent();

		$provinsi = $this->getProvinceName($alamat['province_id']);
		if (!$provinsi->isSuccess()) return;
		$provinsi = $provinsi->getContent();

		$interactive = $this->session->createInteractive(null, $alamat['name'], $alamat['owner_name']);
		$this->session->sendInteractiveMessage($interactive);

		$text = $alamat['address'] . "\n" . $kecamatan
			. ", Kota/Kab " . $kota
			. "\n" . $provinsi
			. "\nTelephone/Handphone: " . $alamat['owner_phone'];
		$this->session->sendMessage($text);

		$buttons = [
			$this->session->createButton('/alamat_daftar', 'Kembali ke Daftar Alamat'),
			$this->session->createButton('/menu', 'Kembali ke Menu Utama')
		];
		$interactive = $this->session->createInteractive(null, null, null, $buttons);
		$this->session->sendInteractiveMessage($interactive);

		return;
	}

	private function setToDefault($id) {

		$parameter = [
			'default' => 'true'
		];

		$result = $this->post('v1/fjb/location/addresses/' . $id, $parameter);
		if (!$result->isSuccess()) return;

		$this->session->setLastSession('alamat_default');

		$buttons = [
			$this->session->createButton('/alamat_daftar', 'Kembali ke Daftar Alamat'),
			$this->session->createButton('/menu', 'Kembali ke Menu Utama')
		];
		$title = "Alamat Utama Berhasil Diubah";
		$interactive = $this->session->createInteractive(null, $title, null, $buttons);

		$this->session->sendInteractiveReply($interactive);
	}

	private function sendDeleteConfirmation($id) {

		$this->session->setLastSession('alamat_delete_confirmation');
		$response = $this->get('v1/fjb/location/addresses');
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$alamat_exist = false;
		$alamat = [];
		foreach ($response['data'] as $a) {

			if ($a['id'] == $id) {

				$alamat_exist = true;
				$alamat = $a;
				break;
			}
		}

		if (!$alamat_exist) {

			$this->session->sendReply('Alamat tidak ditemukan.');
			return;
		}

		$kecamatan = $this->getAreaName($alamat['area_id']);
		if (!$kecamatan->isSuccess()) return;
		$kecamatan = $kecamatan->getContent();

		$kota = $this->getCityName($alamat['city_id']);
		if (!$kota->isSuccess()) return;
		$kota = $kota->getContent();

		$provinsi = $this->getProvinceName($alamat['province_id']);
		if (!$provinsi->isSuccess()) return;
		$provinsi = $provinsi->getContent();

		$text = $alamat['owner_name'] . "\n"
			. $alamat['address'] . "\n"
			. $kecamatan . ", Kota/Kab " . $kota . "\n"
			. $provinsi . "\nTelephone/Handphone: " . $alamat['owner_phone'];
		$this->session->sendMessage($text);

		$buttons = [
			$this->session->createButton('ya', 'Ya'),
			$this->session->createButton('/alamat_daftar', 'Tidak')
		];
		$title = $alamat['name'];
		$caption = 'Apakah anda yakin ingin menghapus alamat ini?';
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);
		$this->session->sendInteractiveMessage($interactive);

		$this->session->setLastSession('alamat_delete_' . $id);
	}

	private function deleteAlamat($id) {

		$confirmation = $this->session->message;
		if ($confirmation != 'ya') {

			$this->sendDeleteConfirmation($id);
			return;
		}

		$result = $this->delete('v1/fjb/location/addresses/' . $id);
		if (!$result->isSuccess()) return;

		$buttons = [
			$this->session->createButton('/alamat_daftar', 'Kembali ke Daftar Alamat'),
			$this->session->createButton('/menu', 'Kembali ke Menu Utama')
		];
		$title = "Alamat Berhasil Dihapus";
		$interactive = $this->session->createInteractive(null, $title, null, $buttons);

		$this->session->sendInteractiveReply($interactive);
	}

}