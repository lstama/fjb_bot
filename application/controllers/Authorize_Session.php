<?php
/**
 * Created by PhpStorm.
 * User: tes
 * Date: 28/07/2017
 * Time: 20.25
 */

include_once 'Session.php';

class Authorize_Session  extends Session {

	private $token;
	private $oauth_token;
	private $oauth_verifier;

	public function authorizeSession() {

		$this->session_from_database = $this->session_model->find_token($this->oauth_token);

		$this->username = $this->session_from_database['username'];
		$this->setJID($this->session_from_database['JID']);

		$this->kaskus_client->setCredentials($this->message, $this->session_from_database['token_secret']);

		$this->access_token = $this->kaskus_client->getAccessToken();

		if ($this->access_token['access'] === 'GRANTED') {

			#username tidak sama dengan username login
			if ($this->username != $this->access_token['username']) {

				$this->differentAccountAuthorization();
				return;
			}

			$this->authorizationSuccess();
			return;

		}

		if ($this->access_token['access'] === 'DENIED') {

			$this->authorizationFailed();
			return;
		}
	}

	private function authorizationSuccess() {

		$this->status = 'logged_on';
		$this->last_session = 'logged_on';

		$data = array(
			'token' => $this->access_token['oauth_token'],
			'token_secret' => $this->access_token['oauth_token_secret'],
			'last_session' => $this->last_session,
			'userid' => $this->access_token['userid'],
			'user' => $this->access_token['username']
		);

		$this->session_model->update_session($this->session_from_database['username'], $data);
		$this->message = '/menu';

		$this->redirectToMenuUtama();
		echo "Authorisasi berhasil. Silakan kembali ke apps untuk mulai melanjutkan.";
	}

	private function differentAccountAuthorization() {

		$this->renewAuthorization();
		echo "Authorisasi gagal, akun Kaskus Chat dan akun authorisasi berbeda. 
					Silakan logout terlebih dahulu dari akun authorisasi yang sekarang (dari browser). 
					Kami telah mengirimkan link authorisasi yang baru, silakan buka Kaskus Chat lagi.";
	}

	private function authorizationFailed() {

		$this->renewAuthorization();
		echo "Authorisasi gagal. Silakan kembali ke apps untuk mendapatkan link authorisasi yang baru.";
	}

	private function renewAuthorization() {

		$this->status = 'trying_to_login';
		$this->last_session = 'trying_to_login';

		$this->session_model->delete_session($this->session_from_database['username']);

		$this->startSession(TRUE);
	}

	private function redirectToMenuUtama() {

		$this->setLastSession('menu');

		$buttons = [$this->createButton('/menu', 'Menu Utama')];
		$title = 'Login Berhasil';
		$caption = "Silakan klik tombol di bawah ini untuk melanjutkan.";
		$interactive = $this->createInteractive(null, $title, $caption, $buttons);

		$this->sendInteractiveMessage($interactive);
	}

	public function setToken($token) {

		$this->token = $token;
	}

	public function setOauthToken($oauth_token) {

		$this->oauth_token = $oauth_token;
	}

	public function setOauthVerifier($oauth_verifier) {

		$this->oauth_verifier = $oauth_verifier;
	}

}