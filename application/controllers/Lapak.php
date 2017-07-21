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

				$this->search($session_suffix);
				break;

			case 'list':

				$this->searchNext($session_suffix);
				break;

			default:

				$this->sendUnrecognizedCommandDialog();
		}
	}

	public function sendSearchInstruction() {

		$this->session->setLastSession('lapak_search_1');
		$this->session->sendReply('Silakan masukkan barang yang ingin dibeli.');

	}

	public function search($page) {

		$search_query = $this->session->message;
		$this->session->setLastSession('lapak_list_' . $page . "_" . $search_query);

		$response = $this->get('search/lapak', ['query' => ['q' => $search_query, 'page' => $page]]);
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

		$counter = 0; #maximum counter = 10
		$multiple_interactive = [];
		foreach ($response['item'] as $lapak) {

			if (! isset($lapak['payment_mechanism'])) continue;
			if (! in_array('3', $lapak['payment_mechanism'])) continue;

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
			$interactive= $this->session->createInteractive(null, $title, null, $buttons);
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

	public function toRupiah($number) {

		$money_number = 'Rp ';
		$money_number .= number_format($number,2,',','.');
		return $money_number;
	}

	public function searchNext($last_session) {

		$page 	= $this->getPrefix($last_session);
		$barang = $this->getSuffix($last_session);
		if ($this->session->message == 'prev') {
			$page -= 1;
		}
		elseif ($this->session->message == 'next') {
			$page += 1;
		}
		elseif ($this->session->message == 'back') {
			#do nothing;
		}
		else {
			$this->sendUnrecognizedCommandDialog();
			return;
		}

		$this->session->message = $barang;
		$this->search($page);
	}

	public function showDetails($thread_id) {

		$response = $this->get('v1/post/' . $thread_id);
		if (! $response->isSuccess()) return;
		$response = $response->getContent();

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
			$photo = $this->session->createInteractive($thumbnail_url,null,null, $buttons);
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

		$buttons = [
			$this->session->createButton('/buy_' . $response['thread']['thread_id'], 'Beli'),
			$this->session->createButton('back', 'Kembali Ke Pencarian'),
			$this->session->createButton('/menu', 'Kembali Ke Menu Utama')
		];

		$interactive = $this->session->createInteractive(null, null, null, $buttons);
		$this->session->sendInteractiveMessage($interactive);

		return;
	}


}


//	public function main($command) {
//
//		$command = explode('_', $command, 2);
//
//		switch ($command[0]) {
//			case 'start':
//				$this->showSearchInstruction();
//				break;
//
//			case 'details':
//				$this->showDetails($command[1]);
//				break;
//
//			default:
//				$this->unrecognizedCommand();
//		}
//
//	}
//
//	public function lastSessionSpecific($last_session) {
//
//		$last_session = explode('_', $last_session, 2);
//		switch ($last_session[0]) {
//			case 'search':
//				$this->search($last_session[1]);
//				break;
//
//			case 'list':
//				$this->searchNext($last_session[1]);
//				break;
//			default:
//				$this->unrecognizedCommand();
//		}
//	}
//
//	public function showSearchInstruction() {
//
//		$this->session->setLastSession('lapak_search_1');
//		$sender = new Sender;
//		$sender->sendReply('Silakan masukkan barang yang ingin dibeli.');
//
//	}
//
//	public function showDetails($thread_id) {
//
//		$response = $this->get('v1/post/' . $thread_id, []);
//		if (! $response['success']) return;
//		$response = $response['result'];
//
//		#var_dump($response);
//
//		$sender = new Sender;
//		$title = $response['thread']['title'];
//		$price = "Harga : " . $this->toRupiah($response['thread']['discounted_price']);
//
//		if ($response['thread']['discount'] > 0) {
//
//			$price .= "\nHarga sebelum diskon : " . $this->toRupiah($response['thread']['item_price']);
//		}
//
//		$i['interactive'] = $sender->interactive(null, $title, $price, null, null);
//
//		#title&price
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
//
//		$photos['interactives'] = [];
//		foreach ($response['thread']['resources']['images_thumbnail'] as $key => $value) {
//
//			$full_size = $response['thread']['resources']['images'][$key];
//			$photo = $sender->interactive($value,null,null,[$sender->button($full_size, 'Lihat Ukuran Penuh')],null);
//			array_push($photos['interactives'], $photo);
//		}
//
//		#var_dump($photos);
//
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $photos);
//
//		$loc = new FJBOld();
//		#var_dump($loc);
//		$attribute = "Lokasi : " . $loc->location[$response['thread']['item_location']];
//		$attribute .= "\nKondisi : " . $loc->condition[$response['thread']['item_condition']];
//
//		if (isset($response['thread']['shipping']['weight'])) {
//
//			$attribute .= "\nBerat : " . $response['thread']['shipping']['weight'] . " gram";
//		}
//
//		foreach ($response['thread']['extra_attributes'] as $a) {
//
//			$attribute .= "\n" . $a['attribute'] . " : " . $a['value'];
//		}
//
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $attribute);
//
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $response['posts'][0]['post'][0]['text']);
//
//		$button = array($sender->button('/buy_' . $response['thread']['thread_id'], 'Beli'), $sender->button('back', 'Kembali Ke Pencarian'), $sender->button('/menu', 'Kembali Ke Menu Utama'));
//
//		$i['interactive'] = $sender->interactive(null, null, null, $button, null);
//
//		#button
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
//
//		#$this->session->setLastSession('lapak_details_' . $thread_id);
//		return;
//
//	}
//
//	public function searchNext($command) {
//
//		$command = explode('_', $command, 2);
//		$page = $command[0];
//		if ($this->session->content['message'] == 'prev') {
//			$page -= 1;
//		}
//		elseif ($this->session->content['message'] == 'next') {
//			$page += 1;
//		}
//		elseif ($this->session->content['message'] == 'back') {
//			#do nothing;
//		}
//		else {
//			$this->unrecognizedCommand();
//			return;
//		}
//		$barang = $command[1];
//		$this->session->content['message'] = $barang;
//		$this->search($page);
//	}
//
//	public function search($page) {
//
//		$barang = $this->session->content['message'];
//		$this->session->setLastSession('lapak_list_' . $page . "_" . $barang);
//
//		$response = $this->get('search/lapak', ['query' => ['q' => $barang, 'page' => $page]]);
//		if (! $response['success']) return;
//		$response = $response['result'];
//
//		#var_dump($response);
//		#return;
//		#Retrieve success
//		$sender = new Sender();
//
//		$counter = 0; #maximum counter = 10
//		$i['interactives'] = [];
//		foreach ($response['item'] as $a) {
//
//			if (! isset($a['payment_mechanism'])) continue;
//			if (! in_array('3', $a['payment_mechanism'])) continue;
//
//
//			$counter += 1;
//			if ($counter == 11) break;
//
//			$t = $a['title'];
//			$p = "Harga : " . $this->toRupiah($a['discounted_price']);
//
//			if ($a['discount'] > 0) {
//
//				$p .= "\nHarga sebelum diskon : " . $this->toRupiah($a['item_price']);
//			}
//
//			$image = $a['resources']['thumbnail'];
//
//			$b = array($sender->button('/lapak_details_' . $a['post_id'], 'Detail'));
//
//			$temp = $sender->interactive($image, $t, $p, $b, null);
//
//			array_push($i['interactives'], $temp);
//
//		}
//
//		if ($response["total_pages"] == 0) {
//
//			$b = array($sender->button('/menu', 'Kembali ke Menu Utama.'));
//			$tmp['interactive'] = $sender->interactive(null, "Barang Tidak Ditemukan", null, $b, null);
//			$i = $tmp;
//			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
//			return;
//		}
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
//
//		$b = [];
//		if ($page > 1) {
//
//			array_push($b, $sender->button('prev', 'Halaman Sebelumnya'));
//		}
//
//		if ($page < $response['total_pages']) {
//
//			array_push($b, $sender->button('next', 'Halaman Selanjutnya'));
//		}
//
//		array_push($b, $sender->button('/menu', 'Kembali Ke Menu Utama'));
//		$tp['interactive'] = $sender->interactive(null, null, null, $b, null);
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $tp);
//
//		return;
//	}
//
//
//
//	public function toRupiah($number) {
//
//		$money_number = 'Rp ';
//		$money_number .= number_format($number,2,',','.');
//		return $money_number;
//	}
//
//	public function get($url, $query = null) {
//
//		try {
//
//			$response = $this->session->oauth_client->get($url, $query);
//			$temp = $response->json();
//		}
//		catch (\Kaskus\Exceptions\KaskusRequestException $exception) {
//			// Kaskus Api returned an error
//			$response =  $exception->getMessage();
//		}
//		catch (\Exception $exception) {
//			// some other error occured
//			$response =  $exception->getMessage();
//		}
//
//		#error occured
//		if ( (gettype($response) == 'string') or (isset($temp) == FALSE) ) {
//
//			$this->errorOccured();
//			echo $response;
//			return ['success' => false, 'result' => ''];
//		}
//
//		return ['success' => true, 'result' => $temp];
//	}
//
//	public function post($url, $parameter) {
//
//		try {
//
//			$response = $this->session->oauth_client->post($url,['body' => $parameter]);
//			$temp = $response->json();
//		}
//		catch (\Kaskus\Exceptions\KaskusRequestException $exception) {
//			// Kaskus Api returned an error
//			$response =  $exception->getMessage();
//		}
//		catch (\Exception $exception) {
//			// some other error occured
//			$response =  $exception->getMessage();
//		}
//
//		#error occured
//		if ( (gettype($response) == 'string') or (isset($temp) == FALSE) ) {
//
//			$this->errorOccured();
//			echo $response;
//			return ['success' => false, 'result' => ''];
//		}
//
//		return ['success' => true, 'result' => $temp];
//	}
//
//	public function delete($url, $parameter = null) {
//
//		try {
//
//			$response = $this->session->oauth_client->delete($url,['body' => $parameter]);
//			$temp = $response->json();
//		}
//		catch (\Kaskus\Exceptions\KaskusRequestException $exception) {
//			// Kaskus Api returned an error
//			$response =  $exception->getMessage();
//		}
//		catch (\Exception $exception) {
//			// some other error occured
//			$response =  $exception->getMessage();
//		}
//
//		#error occured
//		if ( (gettype($response) == 'string') or (isset($temp) == FALSE) ) {
//
//			$this->errorOccured();
//			echo $response;
//			return ['success' => false, 'result' => ''];
//		}
//
//		return ['success' => true, 'result' => $temp];
//	}
//
//	public function unrecognizedCommand() {
//
//		$this->session->setLastSession('unrecognizedCommand');
//
//		$sender = new Sender();
//		$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
//		$i['interactive'] = $sender->interactive(null, "Perintah Tidak Dikenal", "Silakan masukkan perintah yang benar atau kembali ke menu utama.", $b, null);
//
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
//		return;
//	}
//
//	public function errorOccured() {
//
//		$this->session->setLastSession('errorOccured');
//
//		$sender = new Sender();
//		$b = array($sender->button('/menu', 'Kembali ke Menu Utama'));
//		$i['interactive'] = $sender->interactive(null, "Terjadi Kesalahan pada Server", "Silakan kembali ke menu utama.", $b, null);
//
//		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
//		return;
//	}
//}
