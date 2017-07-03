<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('oauthenticate'))
{
    function oauthenticate($username, $password) {
    	
    	#todo: Waiting for kaskus api to complete this code
    	#temp:
    	$result['status'] = true;
    	$result['token'] = true;
    	$result['token_secret'] = true;
    	
    	return $result;
	}   
}

#todo
if ( ! function_exists('generateOAuthBaseString'))
{
    function generateOAuthBaseString($method, $url, $parameters) {
        
        #Method always uppercase e.g. GET, POST
        #Parameters are all parameter in url and request body (if form encoded) (all oauth* parameter included)
        $result = strtoupper($method) . '&';
        $result .= urlencode($url) . '&';

        #Generate parameter string
        $temp = [];
        foreach ($parameters as $key => $value) {
            
            $temp[urlencode($key)] = urlencode($value);

        }
        ksort($temp);
        $params = "";
        $first = true;
        foreach ($temp as $key => $value) {
            
            if (!$first) $params .='&';
            $params .= $key;
            $params .= '=';
            $params .= $value;
            $first = false;

        }

        $result .= urlencode($params);
        return $result;
    }   
}

if ( ! function_exists('generateOAuthSigningKey'))
{
    function generateOAuthSigningKey($consumer_secret, $token_secret = '') {
        
        $result = urlencode($consumer_secret) . '&' . urlencode($token_secret);
        return $result;
    }   
}

if ( ! function_exists('generateOAuthParameter'))
{
    function generateOAuthParameter($url, $method, $parameter = [], $token = null, $token_secret = '') {
        
        $temp = & get_instance();
        $temp->load->helper('auth_helper');
        $temp->load->helper('oauth_helper');

        $result['oauth_consumer_key']       = getenv('BOT_CONSUMER_KEY');
        $result['oauth_nonce']              = generateNonce();
        $result['oauth_signature_method']   = 'HMAC-SHA1';
        $result['oauth_timestamp']          = time();
        $result['oauth_version']            = '1.0';

        if ($token != null) {

            $result['oauth_token'] = $token;

        }

        $result = array_merge($result, $parameter);

        $key                        = generateOAuthSigningKey(getenv('BOT_CONSUMER_SECRET'),$token_secret);
        $base_string                = generateOAuthBaseString($method, $url, $result);
        $result['oauth_signature']  = generateSHA1Signature($base_string, $key);

        return $result;
    }   
}

if ( ! function_exists('oAuthHeader'))
{
    function oAuthHeader($parameters) {
        
        $temp = [];
        foreach ($parameters as $key => $value) {
            
            $temp[urlencode($key)] = urlencode($value);

        }
        ksort($temp);
        $result = "";
        $first = true;
        foreach ($temp as $key => $value) {
            
            if (substr($key, 0, 5) != "oauth") {
                continue;
            }

            if (!$first) $result .=',';
            $result .= ' ';
            $result .= $key;
            $result .= '=';
            $result .= '"';
            $result .= $value;
            $result .= '"';
            $first = false;

        }



        return 'OAuth' . $result;

    }   
}
