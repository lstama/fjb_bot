<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('checkSessionStatus'))
{
    function checkSessionStatus($user) {
    	
    	$temp = & get_instance();
    	$temp->load->model('session_model');

    	$session = $temp->session_model->find_session($user->username);

        #Session isnt exist.
    	if (empty($session)) {

    		return 'no_session';

    	}

        #Session expired.
		$last_change = $session['last_change'];
		$timeout = 600; #in second
		
        if ((time() - $last_change) > $timeout) {

			return 'session_expired';

		}

        #Session in 'trying to login' state.
		if ($session['last_session'] == 'trying_to_login') {

			return 'trying_to_login';

		}

		return 'logged_on';

	}   
}

if ( ! function_exists('verifyUser'))
{
    function verifyUser($bot_account, $user, $password) {
    	
    	$temp = & get_instance();
    	$temp->load->helper('oauth_helper');
    	$temp->load->model('session_model');
    	
        $result = oauthenticate($user->username, $password);

        #Verify success.
    	if ($result['status']) {
    		
    		$data = array(
                'last_session'   => 'logged_on',
                'token'          => $result['token'],
                'token_secret'   => $result['token_secret']
      		    );

    		$temp->session_model->update_session($user->username, $data);
    		
            #To main menu
            handleReceivedMessage($bot_account, $user, 'menu');

    	}

        #Verify failed.
        else {

            #Ask password.
    		sendReply($user, 'Hai '.$user->username."! \nSilakan masukkan password anda untuk melanjutkan.");

    	}

	}   
}

if ( ! function_exists('createSession'))
{
    function createSession($user) {
    	
    	$temp = & get_instance();
    	$temp->load->model('session_model');
		
        $data = array(
			'username'       => $user->username,
			'token'          => 'token',
			'token_secret'   => 'token_secret',
			'last_session'   => 'trying_to_login'
			);

		$temp->session_model->create_session($data);

	}   
}

if ( ! function_exists('deleteSession'))
{
    function deleteSession($user) {
    	
    	$temp = & get_instance();
    	$temp->load->model('session_model');

		$temp->session_model->delete_session($user->username);
	}   
}