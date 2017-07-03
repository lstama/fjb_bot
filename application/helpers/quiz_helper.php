<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('createQuiz'))
{
    function createQuiz($user) {

    	$temp = & get_instance();
    	$temp->load->helper('format_helper');
    	$temp->load->helper('send_helper');
    	$temp->load->model('quiz_model');
    	$temp->load->model('dota_model');
    	
    	$quiz = rand(1,564);
    	$dota = $temp->dota_model->find_dota($quiz);
    	$data['quiz'] = $quiz;
    	$data['username'] = $user->username;
    	$temp->quiz_model->create_quiz($data);

    	$wrong1 = rand(1,564);
    	while ($wrong1 == $quiz){
    		$wrong1 = rand(1,564);
    	}

    	$wrong2 = rand(1,564);
    	while (($wrong2 == $quiz) or ($wrong2 == $wrong1)){
    		$wrong2 = rand(1,564);
    	}

    	$wrong3 = rand(1,564);
    	while (($wrong3 == $quiz) or ($wrong3 == $wrong1)or ($wrong3 == $wrong2)){
    		$wrong3 = rand(1,564);
    	}
  		
  		$choice = array($quiz,$wrong3,$wrong2,$wrong1);
  		sort($choice);

  		foreach ($choice as $key => $val) {

  			$dot = $temp->dota_model->find_dota($val);
  			$buttons[$key] = button($val,$dot['skill_name']);

  		}


    	$image = "https://www.dotafire.com" . $dota['skill_img'];
    	$caption = "Apa nama skill di atas?";
    	$i['interactive'] = interactive($image,null,$caption,$buttons,null);
    	sendReply($user,$i);
    }
}

if ( ! function_exists('answerQuiz'))
{
    function answerQuiz($user, $message) {

    	$temp = & get_instance();
    	$temp->load->helper('format_helper');
    	$temp->load->helper('send_helper');
    	$temp->load->model('quiz_model');
    	$temp->load->model('dota_model');
    	$right_answer = $temp->quiz_model->find_quiz($user->username);
		$dota = $temp->dota_model->find_dota($right_answer['quiz']);

		if ($message != $right_answer['quiz']) {

			$caption 			= "Maaf, jawaban agan salah.\nGambar di atas adalah " . $dota['skill_name'] . ", skill milik " . $dota['hero_name'] . ".\nKlik tombol di bawah jika agan ingin menjawab quiz yang lain.";
			$button				= array(button('quiz', 'Mulai baru!'),button('menu', 'Menu Utama!'));
			$i['interactive'] 	= interactive(null,null,$caption,$button,null);
			sendReply($user, $i);

		}

		else {

			$caption 			= "Jawaban agan benar!.\nGambar di atas adalah " . $dota['skill_name'] . ", skill milik " . $dota['hero_name'] . ".\nKlik tombol di bawah jika agan ingin menjawab quiz yang lain.";
			$button				= array(button('quiz', 'Mulai baru!'),button('menu', 'Menu Utama!'));
			$i['interactive'] 	= interactive(null,null,$caption,$button,null);
			sendReply($user, $i);

		}

    }
}