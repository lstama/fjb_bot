<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;


if ( ! function_exists('sendToChatApi'))
{
    function sendToChatApi($auth, $content_type, $body) {

    	$client = new Client(['http_errors' => false]);
    	
		$header["Content-Type"]  = $content_type;
		$header["Authorization"] = $auth;
		$result 		 		 = $client->post('https://api.obrol.id/api/v1/bot/send-mass', ['verify' => false, 'headers' => $header, 'body' => $body]);
		
		return $result;
		
	}   
}

#todo
if ( ! function_exists('requestPost'))
{
    function requestPost($url, $headers, $body = null) {

    	$client = new Client(['http_errors' => false]);

		$result = $client->post($url, ['verify' => false, 'headers' => $headers, 'body' => $body]);

		return $result;
		
	}   
}

if ( ! function_exists('requestGet'))
{
    function requestGet($url, $headers, $query = null) {

		$client = new Client(['http_errors' => false]);

    	$result = $client->get($url, ['verify' => false, 'headers' => $headers, 'query' => $query]);	
    	
		return $result;
	}   
}