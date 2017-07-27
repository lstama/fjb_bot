<?php

include_once 'Features.php';
include_once 'FJB_Old.php';

class FJB extends Features {


	public $fjb_old;

	public function __construct() {

		$this->fjb_old = new FJB_Old;
	}

	protected function getProvinceNameFromOldKaskus($id) {

		return $this->fjb_old->location[$id];
	}

	protected function getItemConditionName($id) {

		return $this->fjb_old->condition[$id];
	}

	protected function getProvinceName($id) {

		$response = $this->get('v1/fjb/location/provinces/' . $id);
		if (!$response->isSuccess()) {

			return $response;
		}

		return new Request_Result($response->getSuccess(), $response->content['name']);
	}

	protected function getCityName($id) {

		$response = $this->get('v1/fjb/location/cities/' . $id);
		if (!$response->isSuccess()) {

			return $response;
		}

		return new Request_Result($response->getSuccess(), $response->content['name']);
	}

	protected function getAreaName($id) {

		$response = $this->get('v1/fjb/location/areas/' . $id);
		if (!$response->isSuccess()) {

			return $response;
		}

		return new Request_Result($response->getSuccess(), $response->content['name']);
	}

	protected function toRupiah($number) {

		$money_number = 'Rp ';
		$money_number .= number_format($number, 2, ',', '.');
		return $money_number;
	}

	protected function isThreadClosed($response) {

		if ($response['thread']['open'] == 1) {

			return false;
		} else {

			return true;
		}
	}

	protected function sendThreadClosedDialog() {

		$buttons = [
			$this->session->createButton('back', 'Kembali Ke Pencarian'),
			$this->session->createButton('/menu', 'Kembali Ke Menu Utama')
		];
		$title = 'Lapak Sudah Ditutup';
		$caption = 'Silakan kembali ke pencarian atau menu utama';
		$interactive = $this->session->createInteractive(null, $title, $caption, $buttons);
		$this->session->sendInteractiveMessage($interactive);
	}

	protected function sendEmptyAlamatDialog() {

		$this->session->setLastSession('alamat_empty');
		$buttons = [
			$this->session->createButton('/alamat_create', 'Buat Alamat Baru'),
			$this->session->createButton('/menu', 'Kembali ke Menu Utama.')
		];

		$caption = "Anda belum mempunyai alamat yang tersimpan.\nSilakan menambahkan alamat baru.";
		$interactive = $this->session->createInteractive(null, null, $caption, $buttons);
		$this->session->sendInteractiveMessage($interactive);
	}

}