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

// The script is called every 5 min, so 288 times a day, it should tweet randomly a few times per day depending on the number of followers
// The more followers, the more random tweets
function formula($nbfollowers){
    return ($nbfollowers > 1) ? ($nbfollowers*2 / (log(288, 2) * log($nbfollowers, 2)))/144 : 0.5/144;
}

$url = 'https://api.twitter.com/1.1/followers/list.json';
$getfield = '?screen_name=EmergencyPipou';
$requestMethod = 'GET';

$twitter = new TwitterAPIExchange($APIsettings);
$followers = $twitter->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();

$followers = json_decode($followers);
$followers = $followers->users;

// Test if should tweet or not
$p = formula(count($followers));
if(mt_rand() / mt_getrandmax() < $p){
  $randfollower = $followers[array_rand($followers)];
  $screenName = $randfollower->screen_name;

  do{
    $tweet = "@" . $screenName . " " . $phrases[array_rand($phrases)];
  }while(strlen($tweet) > 140)

  // Post the tweet
  $postfields = array(
      'status' =>  $tweet);
  $url = "https://api.twitter.com/1.1/statuses/update.json";
  $requestMethod = "POST";

  echo $twitter->resetFields()
                ->buildOauth($url, $requestMethod)
                ->setPostfields($postfields)
                ->performRequest();
}
 ?>
