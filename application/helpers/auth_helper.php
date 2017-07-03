<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('generateKaskusBotSignature'))
{
    function generateKaskusBotSignature($hookSecret, $httpBody, $httpDate) {
    	
    	$stringToEncode = $httpDate . $httpBody;
    	$hashedString 	= base64_encode(hash_hmac('sha256', $stringToEncode, $hookSecret, true));
    	
        return $hashedString;

	}   
}

if ( ! function_exists('basicAuthHeader'))
{
    function basicAuthHeader($username, $password) {
    	
    	$stringToEncode = $username . ':' . $password;
    	$hashedString 	= base64_encode($stringToEncode);
    	
        return 'Basic ' . $hashedString;

	}   
}

if ( ! function_exists('generateSHA1Signature'))
{
    function generateSHA1Signature($base_string, $key) {
        
        $result = base64_encode(hash_hmac('sha1', $base_string, $key, true));
        
        return $result;
    }   
}

if ( ! function_exists('generateNonce'))
{
    function generateNonce($salt = 'jackylmao') {
        
        $base_string = time();
        $result = base64_encode(hash_hmac('sha1', $base_string, $salt));
        
        return $result;
    }   
}

if ( ! function_exists('basicAuth'))
{
    function basicAuth($username, $password) {
        
        $stringToEncode = $username . ':' . $password;
        $hashedString   = base64_encode($stringToEncode);
        
        return 'Basic ' . $hashedString;

    }   
}