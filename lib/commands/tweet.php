<?php

/*
 * This file is part of Skylines
 */
namespace Skylines;

use Huxtable\Bot\Corpora;
use Huxtable\Bot\Slack;
use Huxtable\Bot\Twitter;
use Huxtable\CLI\Command;
use Huxtable\Core\File;

date_default_timezone_set( 'UTC' );

/**
 * @command		tweet
 * @desc		Generate a NaMo and tweet it
 * @usage		tweet
 */
$commandTweet = new Command( 'tweet', 'Generate a NaMo and tweet it', function()
{
	GLOBAL $bot;

	$dirCorpora = $this->dirApp
		->childDir( 'lib' )
		->childDir( 'corpora' );

	$bot->setCorporaDirectory( $dirCorpora );

	$exclusions = [
	    "biatch",
	    "bitch",
	    "chink",
	    "crazy",
	    "crip",
	    "cunt",
	    "dago",
	    "daygo",
	    "dego",
	    "dick",
	    "dumb",
	    "dyke",
	    "fag",
	    "gook",
	    "gyp",
	    "homo",
		"jap",
	    "jew",
	    "kike",
	    "lame",
	    "lesbo",
	    "negro",
	    "nigg",
	    "paki",
	    "puss",
		"rag",
		"rape",
	    "retar",
	    "shema",
	    "skank",
	    "slut",
	    "spade",
	    "spic",
	    "tard",
	    "tits",
	    "tran",
	    "twat",
		"woman",
	    "whore"
	];

	/*
	 * Ensure that we don't tweet the most egregious stuff
	 */
	do
	{
		$didFindGoodTweet = true;
		$tweet = $bot->getTweet( $dirCorpora );

		/* Look for not-great stuff */
		$tweetNormalized = strtolower( $tweet );

		foreach( $exclusions as $exclusion )
		{
			if( substr_count( $tweetNormalized, $exclusion ) > 0 )
			{
				$didFindGoodTweet = false;
			}
		}

		$didFindGoodTweet = $didFindGoodTweet && substr_count( $tweetNormalized, 'ii' ) == 0;
		$didFindGoodTweet = $didFindGoodTweet && substr_count( $tweetNormalized, 'nng' ) == 0;
	}
	while( !$didFindGoodTweet );

	if( $this->getOptionValue( 'no-tweet' ) )
	{
		$bot->writeHistory();
		echo $tweet . PHP_EOL;
		return;
	}

	/*
	 * Tweet
	 */
	$tweet = new Twitter\Tweet( $tweet );

	/* Post it */
	try
	{
		$bot->postTweetToTwitter( $tweet );
	}
	catch( \Exception $e )
	{
		/* Slack */
		$message = new Slack\Message();
		$attachment = new Slack\Attachment( "Post to Twitter" );

		$attachment->setColor( 'danger' );
		$attachment->addField( 'Status', 'Failed', true );
		$attachment->addField( 'Message', $e->getMessage(), true );

		$message->addAttachment( $attachment );
		$bot->postMessageToSlack( $message );

		throw new Command\CommandInvokedException( $e->getMessage(), 1 );
	}

	$bot->writeHistory();
});

$commandTweet->registerOption( 'no-tweet' );

return $commandTweet;
