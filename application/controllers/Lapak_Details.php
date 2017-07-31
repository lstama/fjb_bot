<?php

include_once 'FJB.php';

class Lapak_Details extends FJB{

	public function showDetails() {

		$thread_id = $this->getPrefix($this->message_now);
		$response = $this->get('v1/post/' . $thread_id);
		if (!$response->isSuccess()) return;
		$response = $response->getContent();

		$seller_info = $this->get('user/' . $response['posts'][0]['post_userid'] . '?include=feedback');
		if (!$seller_info->isSuccess()) return;
		$seller_info = $seller_info->getContent();

		if ($this->isThreadClosed($response)) {

			$this->sendThreadClosedDialog();
			return;
		}

		$this->sendLapakNameAndPrice($response);
		$this->sendLapakPhotos($response);
		$this->sendLapakAttribute($response);
		$this->sendLapakDetails($response);
		$this->sendSellerInfo($seller_info);
		$this->sendLapakOption($response);
		return;
	}

	private function sendLapakNameAndPrice($response) {

		$title = $response['thread']['title'];
		$price = "Harga : " . $this->toRupiah($response['thread']['discounted_price']);
		if ($response['thread']['discount'] > 0) {

			$price .= "\nHarga sebelum diskon : " . $this->toRupiah($response['thread']['item_price']);
		}
		$interactive = $this->session->createInteractive(null, $title, $price, null);
		$this->session->sendInteractiveMessage($interactive);
	}

	private function sendLapakPhotos($response) {

		$photos = [];
		foreach ($response['thread']['resources']['images_thumbnail'] as $key => $thumbnail_url) {

			$fullsize_url = $response['thread']['resources']['images'][$key];
			$buttons = [$this->session->createButton($fullsize_url, 'Lihat Ukuran Penuh')];
			$photo = $this->session->createInteractive($thumbnail_url, null, null, $buttons);
			array_push($photos, $photo);
		}

		$this->session->sendMultipleInteractiveMessage($photos);
	}

	private function sendLapakAttribute($response) {

		$attribute = "Lokasi : " . $this->getProvinceNameFromOldKaskus($response['thread']['item_location']);
		$attribute .= "\nKondisi : " . $this->getItemConditionName($response['thread']['item_condition']);

		if (isset($response['thread']['shipping']['weight'])) {

			$attribute .= "\nBerat : " . $response['thread']['shipping']['weight'] . " gram";
		}

		foreach ($response['thread']['extra_attributes'] as $extra_attribute) {

			$attribute .= "\n" . $extra_attribute['attribute'] . " : " . $extra_attribute['value'];
		}
		$this->session->sendMessage($attribute);
	}

	private function sendLapakDetails($response) {

		$this->session->sendMessage($response['posts'][0]['post'][0]['text']);
	}

	private function sendLapakOption($response) {

		$post_id = $response['thread']['thread_id'];
		$buttons = [
			$this->session->createButton('/buy_start_' . $post_id, 'Beli'),
			$this->session->createButton('/keranjang_tambah_' . $post_id, 'Tambah ke Keranjang'),
			$this->session->createButton('back', 'Kembali Ke Pencarian'),
			$this->session->createButton('/menu', 'Kembali Ke Menu Utama')
		];

		$interactive = $this->session->createInteractive(null, null, null, $buttons);
		$this->session->sendInteractiveMessage($interactive);
	}

	private function sendSellerInfo($seller_info) {

		$username = $seller_info['username'];
		$profile_picture = $seller_info['profilepicture'];
		$user_title = $seller_info['usertitle'];
		$feedback_percentage = $seller_info['feedback']['percentage'];
		$feedback_point = $seller_info['feedback']['point'];
		$text = $user_title . '\n'
			. 'Feedback : ' . $feedback_percentage . '% (' . $feedback_point . ')';
		$interactive = $this->session->createInteractive($profile_picture, $username, $text);
		$this->session->sendInteractiveMessage($interactive);
	}

}