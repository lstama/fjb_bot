<?php

require __DIR__ . '/../vendor/autoload.php';

// configuration
//$consumerKey = 'YOUR CONSUMER KEY';
//$consumerSecret = 'YOUR CONSUMER SECRET';
$callbackUrl = 'YOUR CALLBACK URL'; // e.g. http://yourapplication.com

// creating client
$client = new \Kaskus\KaskusClient($consumerKey, $consumerSecret);

// attempt to get request token
$requestToken = $client->getRequestToken($callbackUrl);
print_r($requestToken);
//$requestToken =
//[
//    'oauth_token' => '<request token key>',
//    'oauth_token_secret' => '<request token secret>'
//];

// attempt to get authorization from user
$authorizeUrl = $client->getAuthorizeUrl($requestToken['oauth_token']);
print_r($authorizeUrl);
// your app should redirect to $authorizeUrl

// after redirected to kaskus, user has to sign in and authorize the application
// user will be redirected to $callbackUrl
// e.g. http://localhost:8000/callback?token=token-key-here&oauth_token=token-key-here

//$requestToken = [
//    'oauth_token' => '<use your authorized request token key here>',
//    'oauth_token_secret' => '<use your authorized token secret here>'
//];
$client->setCredentials($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
$accessToken = $client->getAccessToken();
print_r($accessToken);

//Error
//$accessToken = [
//    'access' => 'DENIED',
//    'message' => '<Error Message>'
//];

// Success
//$accessToken =
//    [
//        'access' => 'GRANTED',
//        'oauth_token' => '<token key>',
//        'oauth_token_secret' => '<token secret>',
//        'userid' => '<user id>',
//        'username' => '<user name>',
//        'expired' => '<expired time>',
//    ];
