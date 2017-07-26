<?php

require 'vendor/autoload.php';

include_once 'Request_Result.php';

class Request {

	/** @var Session $session */
	protected $session;

	protected function get($url, $query = null) {

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

	protected function post($url, $parameter) {

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

	protected function delete($url, $parameter = null) {

		try {

			$response = $this->session->kaskus_client->delete($url,['body' => $parameter]);
			return $this->createSuccessResult($response);
		}
		catch (\Kaskus\Exceptions\KaskusRequestException $exception) {

			return $this->createRequestExceptionResult($exception);
		}
		catch (\Exception $exception) {

			return $this->createRequestExceptionResult($exception);
		}
	}

	private function createSuccessResult($response) {

		/** @var \GuzzleHttp\Message\Response $response */
		$result = new Request_Result;
		$result->setSuccess(true);
		$result->setContent($response->json());
		return $result;
	}

	private function createRequestExceptionResult($exception) {

		/** @var \Kaskus\Exceptions\KaskusRequestException $exception */
		$response = $exception->getMessage();
		$this->sendErrorOccuredDialog($response);

		$result = new Request_Result;
		$result->setSuccess(false);
		$result->setContent($response);
		return $result;
	}


	protected function sendErrorOccuredDialog($response = null) {

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

}