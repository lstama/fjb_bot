<?php

require 'vendor/autoload.php';

class Features {

	public $session;

	public function get($url, $query = null) {

		try {

			if (isset($query)) {

				$response = $this->session->kaskus_client->get($url, $query);
			} else {

				$response = $this->session->kaskus_client->get($url);
			}

			return $this->createSuccessResult($response);
		}
		catch (\Kaskus\Exceptions\KaskusRequestException $exception) {

			return $this->createRequestExceptionResult($exception);
		}
		catch (\Exception $exception) {

			return $this->createRequestExceptionResult($exception);
		}

	}

	public function post($url, $parameter) {

		try {

			$response = $this->session->kaskus_client->post($url,['body' => $parameter]);
			return $this->createSuccessResult($response);
		}
		catch (\Kaskus\Exceptions\KaskusRequestException $exception) {

			return $this->createRequestExceptionResult($exception);
		}
		catch (\Exception $exception) {

			return $this->createRequestExceptionResult($exception);
		}
	}

	public function createSuccessResult($response) {

		$result = new Request_Result();
		$result->setSuccess(true);
		$result->setContent($response->json());
		return $result;
	}

	public function createRequestExceptionResult($exception) {

		$response = $exception->getMessage();
		$this->errorOccured($response);

		$result = new Request_Result();
		$result->setSuccess(false);
		$result->setContent($response);
		return $result;
	}


	public function errorOccured($response = null) {

		$this->session->setLastSession('error_occured');

		$buttons = [$this->session->createButton('/menu', 'Kembali ke Menu Utama')];
		$text = "Terjadi Kesalahan pada Server.\nSilakan kembali ke menu utama.";
		if (isset($response)) {

			$text = $response;
		}

		$interactive = $this->session->createInteractive(null, null, $text, $buttons, null);

		$this->session->sendInteractiveMessage($interactive);
	}

	public function setSession(Session $session) {

		$this->session = $session;
	}


	#TODO: pindah ke Location extend this
//	public function getProvince($id) {
//
//		$response = $this->get('v1/fjb/location/provinces/' . $id);
//		if (! $response['success']) return ['success' => false, 'result' => ''];
//
//		return ['success' => true, 'result' => $response['result']['name']];
//
//	}
//
//	public function getCity($id) {
//
//		$response = $this->get('v1/fjb/location/cities/' . $id);
//		if (! $response['success']) return ['success' => false, 'result' => ''];
//
//		return ['success' => true, 'result' => $response['result']['name']];
//
//	}
//
//	public function getArea($id) {
//
//		$response = $this->get('v1/fjb/location/areas/' . $id);
//		if (! $response['success']) return ['success' => false, 'result' => ''];
//
//		return ['success' => true, 'result' => $response['result']['name']];
//
//	}
}