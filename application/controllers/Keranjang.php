<?php

include_once 'FJB.php';

class Keranjang extends FJB {

	public function main() {

		$message_prefix = $this->getPrefix($this->message_now);
		$message_suffix = $this->getSuffix($this->message_now);

		switch ($message_prefix) {

			case 'daftar':

				$this->sendDaftarBarang($message_suffix);
				break;

			case 'hapus':

				$this->deleteBarangKeranjang($message_suffix);
				break;

			case 'tambah':

				$this->tambahBarangKeranjang($message_suffix);
				break;

			default:

				$this->lastSessionSpecific();
		}
	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
//		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	private function sendDaftarBarang($cursor) {

		$this->session->setLastSession('keranjang_daftar');
		$query = [
			'query' => [
				'cursor' => $cursor,
				'offer_type' => 1,
				'limit' => 10
			]
		];
		$response = $this->get('v1/fjb/checkout_items/', $query);
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$total_barang = 0; #maximum counter = 10
		$multiple_interactive = [];
		foreach ($response['data'] as $barang) {

			$total_barang += 1;
			if ($total_barang > 10) break;

			$interactive = $this->createBarangInteractive($barang);

			array_push($multiple_interactive, $interactive);
		}

		if ($total_barang == 0) {

			$interactive = $this->createEmptyKeranjangDialog();
			$this->session->sendInteractiveMessage($interactive);
			return;
		} else {

			$this->session->sendMultipleInteractiveMessage($multiple_interactive);
		}

		$buttons = [];
		if ($response['meta']['cursor']['current'] != $response['meta']['cursor']['next']) {

			$next_cursor = $response['meta']['cursor']['next'];
			$buttons = [$this->session->createButton('/keranjang_daftar_' . $next_cursor, 'Next')];
		}
		array_push($buttons, $this->session->createButton('/menu', 'Kembali ke Menu Utama'));
		$interactive = $this->session->createInteractive(null, null, null, $buttons);
		$this->session->sendInteractiveMessage($interactive);

	}

	private function createBarangInteractive($barang) {

		$title = $barang['item']['title'];
		$price = $this->toRupiah($barang['item']['discounted_price']);
		$buttons = [
			$this->session->createButton('/buy_start_' . $barang['item']['id'], 'Beli Sekarang'),
			$this->session->createButton('/keranjang_hapus_' . $barang['id'], 'Hapus')
		];

		$interactive = $this->session->createInteractive($barang['item']['image'], $title, $price, $buttons);

		return $interactive;
	}

	private function createEmptyKeranjangDialog() {

		$buttons = [
			$this->session->createButton('/lapak_start', 'Cari Barang'),
			$this->session->createButton('/menu', 'Kembali ke Menu Utama')
		];

		$caption = "Keranjang masih kosong.";
		$interactive = $this->session->createInteractive(null, null, $caption, $buttons);

		return $interactive;
	}

	private function deleteBarangKeranjang($id) {

		$result = $this->delete('v1/fjb/checkout_items/' . $id);
		if (!$result->isSuccess()) return;

		$buttons = [
			$this->session->createButton('/keranjang_daftar', 'Kembali ke Keranjang'),
			$this->session->createButton('/menu', 'Kembali ke Menu Utama')
		];
		$title = "Barang Berhasil Dihapus dari Keranjang";
		$interactive = $this->session->createInteractive(null, $title, null, $buttons);

		$this->session->sendInteractiveReply($interactive);
	}

	private function tambahBarangKeranjang($thread_id) {

		$response = $this->get('v1/lapak/' . $thread_id, []);
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		if ($this->isThreadClosed($response)) {

			$this->sendThreadClosedDialog();
			return;
		}

		$parameter = [
			'thread_id' => $thread_id,
			'quantity' => 1
		];

		$result = $this->post('v1/fjb/checkout_items', $parameter);
		if (!$result->isSuccess()) return;

		$this->session->setLastSession('keranjang_tambah');
		$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
		$title = "Barang Berhasil Ditambahkan ke Keranjang";
		$interactive = $this->session->createInteractive(null, $title, null, $buttons);

		$this->session->sendInteractiveMessage($interactive);
	}


}