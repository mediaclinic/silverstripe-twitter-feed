<?php
/**
 *
 *
 * @author Mediaclinic -> forked from Tyler Kidd <tyler@adaircreative.com>
 * @date 15.9.2013
 * @package silverstripe-twitter-feed
 */
class TwitterFeedExtension extends SiteTreeExtension {
	

	public static $twitter_cache_enabled = false;
	public static $twitter_cache_key;
	public static $twitter_cache_id;

	public static $twitter_consumer_key;
	public static $twitter_consumer_secret;
	public static $twitter_oauth_token;
	public static $twitter_oauth_token_secret;
	
	public static $twitter_user;
	
	public static $twitter_config = array(
		'include_entities' => 'true',
		'include_rts' => 'true'
	);
	
	public function TwitterFeed($limit = 5){
	
		if(self::$twitter_cache_enabled){
			$cache = SS_Cache::factory(self::$twitter_cache_key);
			$cacheID = self::$twitter_cache_id;
		}

		if(!self::$twitter_cache_enabled || (isset($cache) && (!$output = unserialize($cache->load($cacheID))))){
	
			require_once(Director::baseFolder().'/'.TWITTER_FEED_BASE.'/thirdparty/twitteroauth/twitteroauth.php');

			$connection = new TwitterOAuth(
				self::$twitter_consumer_key,
				self::$twitter_consumer_secret,
				self::$twitter_oauth_token,
				self::$twitter_oauth_token_secret
			);
			
			$config = self::$twitter_config;

			if(self::$twitter_user){
				$config['screen_name'] = self::$twitter_user;
			}
			
			$tweets = $connection->get('statuses/user_timeline', $config);
	
			$tweetList = new ArrayList();
						
			if(count($tweets) > 0 && !isset($tweets->error)){
				$i = 0;
				foreach($tweets as $tweet){
					
					if(++$i > $limit) break;
					
					$date = new SS_Datetime();
					$date->setValue(strtotime($tweet->created_at));
					
					$text = $tweet->text;

					$user = $tweet->user->name;
					$screenname = $tweet->user->screen_name;
					$description = $tweet->user->description;
					$profile_image = $tweet->user->profile_image_url;
					$followers_count = $tweet->user->followers_count;
					$friends_count = $tweet->user->friends_count;
					$favourites_count = $tweet->user->favourites_count;
					$retweet_count = $tweet->retweet_count;
					$favorite_count = $tweet->favorite_count;
					$favorited = $tweet->favorited;
					$retweeted = $tweet->retweeted;
					$retweet_link =  "http://twitter.com/home?status=".urlencode("RT " . $screenname . $text);
					$in_reply_to_link = "http://twitter.com/?status=".$screenname. "&in_reply_to_status_id=".$tweet->id;

					if($tweet->entities && $tweet->entities->urls){
						foreach($tweet->entities->urls as $url){
							$text = str_replace($url->url, '<a href="'.$url->url.'" target="_blank" title="'.$url->url.'">'.$url->url.'</a>',$text);
						}
					}

					if($tweet->entities && $tweet->entities->media){
						foreach($tweet->entities->media as $media){
							$text = str_replace($media->url, '<a href="'.$media->url.'" target="_blank">'.$media->url.'</a>',$text);
						}
					}

					if($tweet->entities && $tweet->entities->urls){
						foreach($tweet->entities->user_mentions as $user_mention){
							$text = str_replace($user_mention->screen_name, '<a href="//twitter.com/'.$user_mention->screen_name.'" target="_blank" title="'.$user_mention->name.'">'.$user_mention->screen_name.'</a>',$text);
						}
					}

					$tweetList->push(
						new ArrayData(array(
							'Title' => $text,
							'Date' => $date,
							'User' => $user,
							'ScreenName' => $screenname,
							'ProfileImage' => $profile_image,
							'Retweet' => $retweet_link,
							'Reply' => $in_reply_to_link
							
						))
					);
	
				}
			}
			
			$view = new ViewableData();
			$output = $view->renderWith('TwitterFeed', array('Tweets' => $tweetList));
			
			if(self::$twitter_cache_enabled){
				$cache->save(serialize($output), $cacheID);
			}
		}
		
		return $output;

	}
	
}
