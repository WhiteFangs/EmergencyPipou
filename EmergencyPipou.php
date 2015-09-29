<?php

include("./Phrases.php");
require_once('./TwitterAPIExchange.php');
header('Content-Type: text/html; charset=utf-8');

/** Set access tokens here - see: https://apps.twitter.com/ **/
$APIsettings = array(
    'oauth_access_token' => "YOUR_ACCESS_TOKEN",
    'oauth_access_token_secret' => "YOUR_ACCESS_TOKEN_SECRET",
    'consumer_key' => "YOUR_CONSUMER_KEY",
    'consumer_secret' => "YOUR_CONSUMER_KEY_SECRET"
);

$url = 'https://api.twitter.com/1.1/followers/list.json';
$getfield = '?screen_name=EmergencyPipou';
$requestMethod = 'GET';

$twitter = new TwitterAPIExchange($APIsettings);
$followers = $twitter->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();

$followers = json_decode($followers);
$followers = $followers->users;
$randfollower = $followers[array_rand($followers)];
$screenName = $randfollower->screen_name;

$tweet = "@" . $screenName . " " . $phrases[array_rand($phrases)];

// Post the tweet
$postfields = array(
    'status' =>  $tweet);
$url = "https://api.twitter.com/1.1/statuses/update.json";
$requestMethod = "POST";

$twitter = new TwitterAPIExchange($APIsettings);
echo $twitter->buildOauth($url, $requestMethod)
              ->setPostfields($postfields)
              ->performRequest();

 ?>
