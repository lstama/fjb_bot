<?php

require 'vendor/autoload.php';
include_once 'Sender.php';

class Lapak extends CI_Controller {

	public $session;

	public function __construct($sess) {

		$this->session = $sess;
		parent::__construct();
	}

	public function main($command) {

		$command = explode('_', $command, 2);

		switch ($command[0]) {
		    case 'start':
		        $this->showSearchInstruction();
		        break;

		    case 'details':
		        $this->showDetails($command[1]);
		        break;

		    default:
		        $this->unrecognizedCommand();
		}

	}

	public function lastSessionSpecific($last_session) {

		$last_session = explode('_', $last_session, 2);
		switch ($last_session[0]) {
		    case 'search':
		        $this->search($last_session[1]);
		        break;

		    case 'list':
		        $this->searchNext($last_session[1]);
		        break;
		    default:
		        $this->unrecognizedCommand();
		}
	}

	public function showSearchInstruction() {

		$this->session->setLastSession('lapak_search_1');
		$sender = new Sender;
		$sender->sendReply('Silakan masukkan barang yang ingin dibeli.');

	}

	public function showDetails($thread_id) {

		$this->session->setLastSession('lapak_details_' . $thread_id);

		$response = $this->get('v1/post/' . $thread_id, []);
		if (! $response['success']) return;
		$response = $response['result'];

		var_dump($response);

		$sender = new Sender;
		$title = $response['thread']['title'];
		$price = "Harga : " . $this->toRupiah($response['thread']['discounted_price']);

			if ($response['thread']['discount'] > 0) {

				$price .= "\nHarga sebelum diskon : " . $this->toRupiah($response['thread']['item_price']);
			}

		$button = array($sender->button('/buy_' . $response['thread']['thread_id'], 'Beli'), $sender->button('/menu', 'Kembali Ke Menu Utama'));
		$i['interactive'] = $sender->interactive(null, $title, $price, $button, null);
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
		return;

	}

	public function searchNext($command) {

		$command = explode('_', $command, 2);
		$page = $command[0];
		if ($this->session->content['message'] == 'prev') {
			$page -= 1;
		}
		elseif ($this->session->content['message'] == 'next') {
			$page += 1;
		} 
		else {
			$this->unrecognizedCommand();
		}
		$barang = $command[1];
		$this->session->content['message'] = $barang;
		$this->search($page);
	}

	public function search($page) {

		$barang = $this->session->content['message'];
		$this->session->setLastSession('lapak_list_' . $page . "_" . $barang);

		$response = $this->get('search/lapak', ['query' => ['q' => $barang, 'page' => $page]]);
		if (! $response['success']) return;
		$response = $response['result'];

		#var_dump($response);
		#Retrieve success
		$sender = new Sender();

		$counter = 0; #maximum counter = 10
		$i['interactives'] = [];
		foreach ($response['item'] as $a) {

			if (! isset($a['payment_mechanism'])) continue;
			if (! in_array('3', $a['payment_mechanism'])) continue;


			$counter += 1;
			if ($counter == 11) break;

			$t = $a['title'];
			$p = "Harga : " . $this->toRupiah($a['discounted_price']);

			if ($a['discount'] > 0) {

				$p .= "\nHarga sebelum diskon : " . $this->toRupiah($a['item_price']);
			}

			$image = $a['resources']['thumbnail'];

			$b = array($sender->button('/lapak_details_' . $a['post_id'], 'Detail'));

			$temp = $sender->interactive($image, $t, $p, $b, null);

			array_push($i['interactives'], $temp);

		}

		if ($counter == 0) {

			$b = array($sender->button('/menu', 'Kembali ke Menu Utama.'));
			$tmp['interactive'] = $sender->interactive(null, "Barang Tidak Ditemukan", null, $b, null);
			$i = $tmp;
			$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);
			return;
		}
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $i);

		$b = [];
		if ($page > 1) {

			array_push($b, $sender->button('prev', 'Halaman Sebelumnya'));
		}

		if ($page < $response['total_pages']) {

			array_push($b, $sender->button('next', 'Halaman Selanjutnya'));
		}
		
		$tp['interactive'] = $sender->interactive(null, null, null, $b, null);
		$sender->sendMessage($this->session->content['bot_account'], $this->session->content['user'], $tp);

		return;
	}



	public function toRupiah($number) {

		$money_number = 'Rp ';
		$money_number .= number_format($number,2,',','.');
		return $money_number;
	}

	public function get($url, $query = null) {

		try {

    		$response = $this->session->oauth_client->get($url, $query);
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

	public function post($url, $parameter) {

		try {

    		$response = $this->session->oauth_client->post($url,['body' => $parameter]);
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

	public function delete($url, $parameter = null) {

		try {

    		$response = $this->session->oauth_client->delete($url,['body' => $parameter]);
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
}