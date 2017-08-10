<?php

class Bot_Account extends CI_Controller {

	protected $bot_username;
	protected $bot_password;
	protected $bot_id;
	protected $hook_secret;
	protected $consumer_key;
	protected $consumer_secret;
	protected $callback_url;
	protected $send_mass_api;
	protected $kaskus_api;

	public function __construct() {

		parent::__construct();

		$this->bot_username = getenv('BOT_USERNAME');
		$this->bot_password = getenv('BOT_PASSWORD');
		$this->bot_id = getenv('BOT_ID');
		$this->hook_secret = getenv('BOT_HOOK_SECRET');
		$this->consumer_key = getenv('BOT_CONSUMER_KEY');
		$this->consumer_secret = getenv('BOT_CONSUMER_SECRET');
		$this->callback_url = getenv('BOT_CALLBACK_URL');
		$this->send_mass_api = getenv('BOT_SEND_MASS_API');
		$this->kaskus_api = getenv('BOT_KASKUS_API');
	}
}