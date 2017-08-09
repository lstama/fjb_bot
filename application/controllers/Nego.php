<?php

include_once 'Buy.php';
include_once 'Buy_Nego.php';

class Nego extends Buy {

	public function main() {

		$message_prefix = $this->getPrefix($this->message_now);
		$message_suffix = $this->getSuffix($this->message_now);

		switch ($message_prefix) {

			case 'daftar':

				$this->sendDaftarNego($message_suffix);
				break;

			case 'checkout':

				$checkout = new Buy_Nego();
				$checkout->setMessageNow($message_suffix);
				$checkout->setSessionNow($this->session_now);
				$checkout->setSession($this->session);
				$checkout->sendNegoCheckoutUrl($message_suffix);
				break;

			case 'details':

				$this->sendNegoDetails($message_suffix);
				break;

			default:

				$this->lastSessionSpecific();
		}
	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	private function sendDaftarNego($cursor) {

		$this->session->setLastSession('nego_daftar');
		$query = [
			'query' => [
				'cursor' => $cursor,
				'limit' => 10
			]
		];
		$response = $this->get('v1/fjb/offers/buyer', $query);
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$total_barang = 0; #maximum counter = 10
		$multiple_interactive = [];
		foreach ($response['data'] as $barang) {

			$total_barang += 1;
			if ($total_barang > 10) break;

			$interactive = $this->createNegoInteractive($barang);

			array_push($multiple_interactive, $interactive);
		}

		if ($total_barang == 0) {

			$interactive = $this->createEmptyNegoDialog();
			$this->session->sendInteractiveMessage($interactive);
			return;
		} else {

			$this->session->sendMultipleInteractiveMessage($multiple_interactive);
		}

		$buttons = [];
		if ($response['meta']['cursor']['current'] != $response['meta']['cursor']['next']) {

			$next_cursor = $response['meta']['cursor']['next'];
			$buttons = [$this->session->createButton('/nego_daftar_' . $next_cursor, 'Next')];
		}
		array_push($buttons, $this->session->createButton('/menu', 'Kembali ke Menu Utama'));
		$interactive = $this->session->createInteractive(null, null, null, $buttons);
		$this->session->sendInteractiveMessage($interactive);

	}

	private function createNegoInteractive($item) {

		$image = $item['item']['image'];
		$title = $item['item']['title'];
		$caption = $item['status'];
		$caption .= "\n";
		$caption .= $this->toRupiah($item['price']);

		$reply = '/nego_details_' . $item['id'];
		$buttons = [$this->session->createButton($reply, 'Lihat Detail')];
		return $this->session->createInteractive($image, $title, $caption, $buttons);
	}

	private function createEmptyNegoDialog(){

		$buttons = [
			$this->session->createButton('/lapak_start', 'Cari Barang'),
			$this->session->createButton('/menu', 'Kembali ke Menu Utama')
		];

		$caption = "Belum ada nego yang diterima/ditolak, atau nego sudah expired.";
		$interactive = $this->session->createInteractive(null, null, $caption, $buttons);

		return $interactive;
	}

//	private function sendNegoCheckout($id) {
//
//		$parameter = [
//			'tips' => '0',
//		];
//
//		$response = $this->post('v1/fjb/lapak/' . $buy['thread_id'] . '/buy_now', $parameter);
//		if (!$response->isSuccess()) return;
//		$response = $response->getContent();
//
//		$this->sendCheckoutUrl($response, $buy);
//	}

	private function sendNegoDetails($offer_id) {

		$response = $this->get('v1/fjb/offers/' . $offer_id);
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$this->displayBarangNego($response);
		$this->displayAlamatNego($response['shipping']['location']);
		$this->displayJasaPengirimanNego($response['shipping']);

		$total_price = $response['price'];
		$biaya = 'Total barang : ' . $response['quantity'];
		$biaya .= "\nTotal harga barang : " . $this->toRupiah($total_price);
		$biaya .= "\nBiaya pengiriman : " . $this->toRupiah($response['shipping']['cost']);
		$biaya .= "\nTotal biaya : " . $this->toRupiah($response['shipping']['cost'] + $total_price);

		$this->session->sendMessage($biaya);

		$text = "Pesan dari seller :\n";
		$text .= $response['reply']['message'];

		$this->session->sendMessage($text);

		$buttons = [];
		if ($response['reply']['status'] == 1) {

			$reply = '/nego_checkout_' . $response['id'];
			$buttons = [
				$this->session->createButton($reply, 'Lanjut Bayar')
			];
		}
		array_push($buttons, $this->session->createButton('/nego_daftar', 'Kembali ke Daftar Nego'));
		array_push($buttons, $this->session->createButton('/menu', 'Kembali ke Menu Utama'));
		$interactive = $this->session->createInteractive(null, null, null, $buttons);

		$this->session->sendInteractiveMessage($interactive);
		return;
	}

	private function displayBarangNego($response) {

		$image = $response['item']['image'];
		$title = $response['item']['title'];
		$caption = $response['status'];
		$interactive = $this->session->createInteractive($image, $title, $caption);

		$this->session->sendInteractiveMessage($interactive);
	}

	public function displayAlamatNego($result) {

		$kecamatan = $this->getAreaName($result['area_id']);
		if (!$kecamatan->isSuccess()) return;
		$kecamatan = $kecamatan->getContent();

		$kota = $this->getCityName($result['city_id']);
		if (!$kota->isSuccess()) return;
		$kota = $kota->getContent();

		$provinsi = $this->getProvinceName($result['province_id']);
		if (!$provinsi->isSuccess()) return;
		$provinsi = $provinsi->getContent();

		$text = $result['address'] . "\n"
			. $kecamatan . ", Kota/Kab " . $kota . "\n"
			. $provinsi
			. "\nTelephone/Handphone: " . $result['owner_phone'];
		$this->session->sendMessage($text);
	}

	public function displayJasaPengirimanNego($shipping) {

		$text = $this->toRupiah($shipping['cost']);
		if ($shipping['method']['estimation_time'] != "") {

			$text .= "\n" . $shipping['method']['estimation_time'] . " hari";
		}

		$interactive = $this->session->createInteractive($shipping['method']['image'], $shipping['method']['name'], $text);
		$this->session->sendInteractiveMessage($interactive);
	}
}