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

        $this->bot_username         = getenv('BOT_USERNAME');
        $this->bot_password         = getenv('BOT_PASSWORD');
        $this->bot_id 	            = getenv('BOT_ID');
        $this->hook_secret      	= getenv('BOT_HOOK_SECRET');
        $this->consumer_key     	= getenv('BOT_CONSUMER_KEY');
        $this->consumer_secret  	= getenv('BOT_CONSUMER_SECRET');

        #TODO : getenv('BOT_CALLBACK_URL'); in production.
        $this->callback_url     	= 'https://c5879276.ngrok.io/refactor/fjb_bot/main_hook';

        #TODO : getenv('BOT_SEND_MASS_API'); in production.
		$this->send_mass_api		= 'https://api.obrol.id/api/v1/bot/send-mass';

		#TODO : getenv('BOT_KASKUS_API'); in production.
		$this->kaskus_api			= 'https://webstaging.kaskus.co.id/api/oauth/';
    }
}