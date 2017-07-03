<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('handleReceivedMessage'))
{
    function handleReceivedMessage($bot_account, $user, $message) {

		$temp = & get_instance();
		$temp->load->helper('send_helper');
		$temp->load->helper('format_helper');
		$temp->load->helper('session_helper');
		$temp->load->helper('auth_helper');
		$temp->load->helper('oauth_helper');
		$temp->load->helper('request_helper');
		$temp->load->model('session_model');
		$temp->load->helper('quiz_helper');
		$temp->load->model('quiz_model');


		#'Hello world!' of this bot.
		if ($message == 'halo') {

			sendReply($user, 'Hai '.$user->username.'!');
			return;
		}

		#Check if the user has login session
		$status = checkSessionStatus($user);
		if ($status != 'logged_on') {
			
			#Status: Already asked for password, $message expected to be user password.
			if ($status == 'trying_to_login') {

				#Retrieve token and token secret for this session.
				verifyUser($bot_account, $user, $message);

			}

			#Status: First time using this bot / login session expired.
			else {

				#Create new / renew session.
				deleteSession($user);
				createSession($user); #last_session => trying_to_login

				#Ask password.
				sendReply($user, 'Hai '.$user->username."! \nSilakan masukkan password anda untuk melanjutkan.");
				
			}

			return;

		} 

		#TODO:
		#BOT MAIN FUNCTION
		$session = $temp->session_model->find_session($user->username);
		$user->token 		= $session['token'];
		$user->token_secret = $session['token_secret']; 
		$user->last_session = $session['last_session'];

		if ($user->last_session == 'stalk') {

			$data = array('last_session' => 'after_stalk');
			$temp->session_model->update_session($user->username, $data);

			#Capturing tweets
			$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
			$method = 'GET';
			$parameter['screen_name'] = $message;
			$parameter['count'] = '5';
			$parameter = generateOAuthParameter($url, $method, $parameter);

			$headers["Content-Type"]  = 'application/json';
			$headers["Authorization"] = oAuthHeader($parameter);

			$result = requestGet($url, $headers, $parameter);

			$result = $result->getBody();
			$result = json_decode($result);

			if (array_key_exists('errors', $result)) {
				
				sendReply($user, $result->errors[0]->message);

				return;

			}


			$twitter_username = $message . "\n" . $result[0]->user->description;
			$twitter_name = $result[0]->user->name;
			$twitter_image = $result[0]->user->profile_image_url_https;
			$twitter_image = str_replace('_normal', '_400x400', $twitter_image);
			$twitter_button = button('https://twitter.com/' . $message, 'View Profile');
			$i['interactives'] = [interactive($twitter_image, $twitter_name, '@' . $twitter_username, [$twitter_button], null)];
			foreach ($result as $value) {
					$button = button('https://twitter.com/' . $message . '/status/' . $value->id_str, 'View Tweet');
					$image = null;
					if (property_exists($value, 'entities')) {

						if (property_exists($value->entities, 'media')) {

								$image = $value->entities->media[0]->media_url_https;

						} 

					} 
					array_push($i['interactives'], interactive($image, null, $value->text, [$button], null));
					#https://twitter.com/lstama/status/601363468810383360
			}
			
			sendReply($user,$i);
			return;

		}

		if ($message == 'menu') {

			$data = array('last_session' => 'menu');
			$temp->session_model->update_session($user->username, $data);
			$button = array(button('stalk', 'Stalk Twitter'), button('quiz', 'DOTA 2 Skill Quiz'));
			$i['interactive'] = interactive(null, null, 'Hai '.$user->username."!\nAnda sekarang berada di menu utama.", $button, null);
			sendReply($user,  $i);
			return;
		}

		if ($message == 'stalk') {

			$data = array('last_session' => 'stalk');
			$temp->session_model->update_session($user->username, $data);
			sendReply($user, 'Hai '.$user->username."!\nAnda sekarang berada di menu stalk. Ketik username akun twitter yang ingin di stalk untuk memulai pencarian.");
			return;
		}

		if ($message == 'quiz') {

				$data['last_session'] = 'quiz';
	    		$temp->session_model->update_session($user->username, $data);
	    		$temp->quiz_model->delete_quiz($user->username);	

	    		createQuiz($user);
	    		return;
			}

			elseif ($user->last_session == 'quiz'){

				$data['last_session'] = 'after_quiz';
	    		$temp->session_model->update_session($user->username, $data);

	    		answerQuiz($user, $message);
	    		return;

			}

		#back to main menu
		$button = button('menu', 'Menu Utama');
		$i['interactive'] = interactive(null, null, 'Hai '.$user->username."!\nKlik tombol di bawah ini untuk kembali ke menu utama.", [$button], null);
		sendReply($user,  $i);

	}   
}
