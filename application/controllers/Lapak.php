<?php

include_once 'FJB.php';

class Lapak extends FJB {

	public function main() {

		$message_prefix = $this->getPrefix($this->message_now);
		$message_suffix = $this->getSuffix($this->message_now);

		switch ($message_prefix) {

			case 'start':

				$this->sendSearchInstruction();
				break;

			case 'details':

				$this->showDetails($message_suffix);
				break;

			default:

				$this->lastSessionSpecific();
		}
	}

	public function lastSessionSpecific() {

		$session_prefix = $this->getPrefix($this->session_now);
		$session_suffix = $this->getSuffix($this->session_now);

		switch ($session_prefix) {

			case 'search':

				$this->sendSearchResult($session_suffix);
				break;

			case 'list':

				$this->searchNext($session_suffix);
				break;

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	private function sendSearchInstruction() {

		$this->session->setLastSession('lapak_search_1');
		$this->session->sendReply('Silakan masukkan barang yang ingin dibeli.');

	}

	private function sendSearchResult($page) {

		$search_query = $this->session->message;
		$this->session->setLastSession('lapak_list_' . $page . "_" . $search_query);
		$query = [
			'query' => [
				'q' => $search_query,
				'page' => $page,
				'limit' => 10
			]
		];
		$response = $this->get('search/lapak', $query);
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$counter = 0; #maximum counter = 10
		$multiple_interactive = [];
		foreach ($response['item'] as $lapak) {

			if (!isset($lapak['payment_mechanism'])) continue;
			if (!in_array('3', $lapak['payment_mechanism'])) continue;

			$counter += 1;
			if ($counter == 11) break;

			$title = $lapak['title'];
			$price = "Harga : " . $this->toRupiah($lapak['discounted_price']);
			if ($lapak['discount'] > 0) {

				$price .= "\nHarga sebelum diskon : " . $this->toRupiah($lapak['item_price']);
			}
			$image = $lapak['resources']['thumbnail'];
			$buttons = [$this->session->createButton('/lapak_details_' . $lapak['post_id'], 'Detail')];

			$temp = $this->session->createInteractive($image, $title, $price, $buttons);
			array_push($multiple_interactive, $temp);

		}

		if ($response["total_pages"] == 0) {

			$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama.')];
			$title = "Barang Tidak Ditemukan";
			$interactive = $this->session->createInteractive(null, $title, null, $buttons);
			$this->session->sendInteractiveMessage($interactive);
			return;
		}

		$this->session->sendMultipleInteractiveMessage($multiple_interactive);

		$buttons = [];
		if ($page > 1) {

			array_push($buttons, $this->session->createButton('prev', 'Halaman Sebelumnya'));
		}
		if ($page < $response['total_pages']) {

			array_push($buttons, $this->session->createButton('next', 'Halaman Selanjutnya'));
		}
		array_push($buttons, $this->session->createButton('/menu', 'Kembali Ke Menu Utama'));

		$interactive = $this->session->createInteractive(null, null, null, $buttons);
		$this->session->sendInteractiveMessage($interactive);
		return;
	}

	private function searchNext($last_session) {

		$page = $this->getPrefix($last_session);
		$barang = $this->getSuffix($last_session);
		if ($this->session->message == 'prev') {
			$page -= 1;
		} elseif ($this->session->message == 'next') {
			$page += 1;
		} elseif ($this->session->message == 'back') {
			#do nothing;
		} else {
			$this->sendUnrecognizedCommandDialog();
			return;
		}

		$this->session->message = $barang;
		$this->sendSearchResult($page);
	}

	private function showDetails($thread_id) {

		$response = $this->get('v1/post/' . $thread_id);
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		if ($this->isThreadClosed($response)) {

			$this->sendThreadClosedDialog();
			return;
		}

		$title = $response['thread']['title'];
		$price = "Harga : " . $this->toRupiah($response['thread']['discounted_price']);
		if ($response['thread']['discount'] > 0) {

			$price .= "\nHarga sebelum diskon : " . $this->toRupiah($response['thread']['item_price']);
		}
		$interactive = $this->session->createInteractive(null, $title, $price, null);
		$this->session->sendInteractiveMessage($interactive);

		$photos = [];
		foreach ($response['thread']['resources']['images_thumbnail'] as $key => $thumbnail_url) {

			$fullsize_url = $response['thread']['resources']['images'][$key];
			$buttons = [$this->session->createButton($fullsize_url, 'Lihat Ukuran Penuh')];
			$photo = $this->session->createInteractive($thumbnail_url, null, null, $buttons);
			array_push($photos, $photo);
		}

		$this->session->sendMultipleInteractiveMessage($photos);

		$attribute = "Lokasi : " . $this->getProvinceNameFromOldKaskus($response['thread']['item_location']);
		$attribute .= "\nKondisi : " . $this->getItemConditionName($response['thread']['item_condition']);

		if (isset($response['thread']['shipping']['weight'])) {

			$attribute .= "\nBerat : " . $response['thread']['shipping']['weight'] . " gram";
		}

		foreach ($response['thread']['extra_attributes'] as $extra_attribute) {

			$attribute .= "\n" . $extra_attribute['attribute'] . " : " . $extra_attribute['value'];
		}
		$this->session->sendMessage($attribute);

		#send info lengkap
		$this->session->sendMessage($response['posts'][0]['post'][0]['text']);
		$post_id = $response['thread']['thread_id'];
		$buttons = [
			$this->session->createButton('/buy_start_' . $post_id, 'Beli'),
			$this->session->createButton('/keranjang_tambah_' . $post_id, 'Tambah ke Keranjang'),
			$this->session->createButton('back', 'Kembali Ke Pencarian'),
			$this->session->createButton('/menu', 'Kembali Ke Menu Utama')
		];

		$interactive = $this->session->createInteractive(null, null, null, $buttons);
		$this->session->sendInteractiveMessage($interactive);

		return;
	}
}