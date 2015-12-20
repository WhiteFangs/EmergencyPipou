<?php

$time = time();
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
$twitter = new TwitterAPIExchange($APIsettings);

// FOLLOW BACK FOLLOWERS

// Get followers
$url = 'https://api.twitter.com/1.1/followers/ids.json';
$getfield = '?screen_name=EmergencyPipou';
$requestMethod = 'GET';
$followers = $twitter->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();
echo $followers;
$followers = json_decode($followers);

$followerIds = array();
foreach ($followers->ids as $i => $id) {
    $followerIds[] = $id;
}

// Get friends
$url = 'https://api.twitter.com/1.1/friends/ids.json';
$getfield = '?screen_name=EmergencyPipou';
$requestMethod = 'GET';
$friends = $twitter->resetFields()
    ->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();
echo $friends;
$friends = json_decode($friends);

$friendIds = array();
foreach ($friends->ids as $i => $id) {
    $friendIds[] = $id;
}

// Follow followers that are not friends
$url = "https://api.twitter.com/1.1/friendships/create.json";
$requestMethod = "POST";
foreach($followerIds as $id){
  if(!in_array($id,$friendIds) ){
    $postfields = array('user_id' =>  $id);
    echo $twitter->resetFields()
                  ->buildOauth($url, $requestMethod)
                  ->setPostfields($postfields)
                  ->performRequest();
  }
}

// REPLY TO MENTIONS

$url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json';
$getfield = '?contributor_details=true';
$requestMethod = 'GET';
$mentions = $twitter->resetFields()
    ->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();

$mentions = json_decode($mentions);
foreach ($mentions as $mention) {
  $date = $mention->created_at;
  $date = strtotime($date);
  if($date > $time - 5 * 60){ // check the mentions from last 5 minutes
    // Reply to user
    $tweetTo = "@" . $mention->user->screen_name;
    // Add other mentionned users
    if(count($mention->entities->user_mentions) > 1){
      foreach ($mention->entities->user_mentions as $user_mentioned) {
        if($user_mentioned->screen_name != "EmergencyPipou")
          $tweetTo .= " @" . $user_mentioned->screen_name;
      }
    }
    // Build tweet
    do{
      $tweet = $tweetTo . " " . $phrasesForMentions[array_rand($phrasesForMentions)];
    }while(strlen($tweet) > 140);
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
}

// THEN SURPRISE RANDOM TWEET

// The script is called every 5 min, so 288 times a day, it should tweet randomly a few times per day depending on the number of followers
// The more followers, the more random tweets
function formula($nbfollowers){
    return ($nbfollowers > 1) ? ($nbfollowers*2 / (log(288, 2) * log($nbfollowers, 2)))/144 : 0.5/144;
}

$url = 'https://api.twitter.com/1.1/followers/list.json';
$getfield = '?screen_name=EmergencyPipou';
$requestMethod = 'GET';
$followers = $twitter->resetFields()
    ->setGetfield($getfield)
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
  }while(strlen($tweet) > 140);

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
