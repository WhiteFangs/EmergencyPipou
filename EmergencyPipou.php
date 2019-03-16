<?php

$time = time();
include("./twitterCredentials.php");
include("./Phrases.php");
require_once('./TwitterAPIExchange.php');
header('Content-Type: text/html; charset=utf-8');
$calledEvery = 10; // minutes

/** Set access tokens here - see: https://apps.twitter.com/ **/
$APIsettings = array(
    'oauth_access_token' => $oauthToken,
    'oauth_access_token_secret' => $oauthTokenSecret,
    'consumer_key' => $consumerKey,
    'consumer_secret' => $consumerSecret
);
$twitter = new TwitterAPIExchange($APIsettings);

// FOLLOW BACK FOLLOWERS

// Get followers ids
$url = 'https://api.twitter.com/1.1/followers/ids.json';
$getfield = '?screen_name=EmergencyPipou&count=5000';
$requestMethod = 'GET';
$followers = $twitter->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();
$followers = json_decode($followers);

$followerIds = array();
foreach ($followers->ids as $i => $id) {
    $followerIds[] = $id;
}

// Get followers list
$cursor = "-1";
$followersList = array();
do {
  $url = 'https://api.twitter.com/1.1/followers/list.json';
  $getfield = '?screen_name=EmergencyPipou&count=200&cursor=' . $cursor;
  $requestMethod = 'GET';
  $decodedResponse = $twitter->resetFields()
      ->setGetfield($getfield)
      ->buildOauth($url, $requestMethod)
      ->performRequest();

  $decodedResponse = json_decode($decodedResponse);
  $followersList = array_merge($followersList, $decodedResponse->users);
  $cursor = $decodedResponse->next_cursor_str;
}
while($cursor != "0");

// Get friends
$url = 'https://api.twitter.com/1.1/friends/ids.json';
$getfield = '?screen_name=EmergencyPipou';
$requestMethod = 'GET';
$friends = $twitter->resetFields()
    ->setGetfield($getfield)
    ->buildOauth($url, $requestMethod)
    ->performRequest();
$friends = json_decode($friends);

$friendIds = array();
foreach ($friends->ids as $i => $id) {
    $friendIds[] = $id;
}

// Follow followers that are not friends
foreach($followerIds as $id){
  if(!in_array($id,$friendIds) ){
    $postfields = array('user_id' =>  $id);
    $twitter->resetFields()
                  ->buildOauth("https://api.twitter.com/1.1/friendships/create.json", "POST")
                  ->setPostfields($postfields)
                  ->performRequest();
    $followerToWelcome = null;
    foreach($followersList as $follower) {
        if ($id == $follower->id) {
            $followerToWelcome = $follower->screen_name;
            break;
        }
    }
    if($followerToWelcome != null){
      $welcomeTweet = "@" . $followerToWelcome . " Coucou ! Merci de me suivre, je t'enverrai des câlins aléatoirement mais si t'en as besoin immédiatement, mentionne-moi ! ♥";
      // Post welcoming tweet
      $postfields = array(
          'status' =>  $welcomeTweet);
      echo $twitter->resetFields()
                    ->buildOauth("https://api.twitter.com/1.1/statuses/update.json", "POST")
                    ->setPostfields($postfields)
                    ->performRequest();
    }
  }
}

// Unfollow friends that are not followers
foreach($friendIds as $id){
  if(!in_array($id, $followerIds)){
    $postfields = array('user_id' =>  $id);
    $twitter->resetFields()
                  ->buildOauth("https://api.twitter.com/1.1/friendships/destroy.json", "POST")
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
  if($date > $time - $calledEvery * 60){ // check the mentions from last 5 minutes
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
      'status' =>  $tweet,
      'in_reply_to_status_id' => $mention->id_str);
    $url = "https://api.twitter.com/1.1/statuses/update.json";
    $requestMethod = "POST";
    $twitter->resetFields()
                  ->buildOauth($url, $requestMethod)
                  ->setPostfields($postfields)
                  ->performRequest();
  }
}

// THEN SURPRISE RANDOM TWEET

// The script should tweet randomly a few times per day depending on the number of followers
// The more followers, the more random tweets
function formula($nbfollowers, $calledEvery){
    $timesPerDay = 24 * (60 / $calledEvery);
    return ($nbfollowers > 1) ? ($nbfollowers*2 / (log($timesPerDay, 2) * log($nbfollowers, 2)))/($timesPerDay / 2) : 0.5/($timesPerDay / 2);
}

// Test if should tweet or not
$p = formula(count($followersList), $calledEvery);
if(mt_rand() / mt_getrandmax() < $p){
  $randfollower = $followersList[array_rand($followersList)];
  $screenName = $randfollower->screen_name;

  do{
    $tweet = "@" . $screenName . " " . $phrases[array_rand($phrases)];
  }while(strlen($tweet) > 140);

  // Post the tweet
  $postfields = array(
      'status' =>  $tweet);
  $url = "https://api.twitter.com/1.1/statuses/update.json";
  $requestMethod = "POST";
  $twitter->resetFields()
                ->buildOauth($url, $requestMethod)
                ->setPostfields($postfields)
                ->performRequest();
}
 ?>
