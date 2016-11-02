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

	$corpora = new Corpora\Corpora( $dirCorpora, $bot->getHistoryObject() );

	$body = $corpora->getItem( 'tweets', 'bodies' );
	$month = $corpora->getItem( 'time', 'months' );
	$body = str_replace( '{month}', $month, $body );

	$noun = $corpora->getItem( 'nouns', 'words', 'nouns' );
	$verbData = $corpora->getItem( 'verbs', 'all', 'verbs' );

	$verbStem = $verbData['present'];
	$originalVerbStem = $verbStem;

	/* Conjugate the verb, badly */
	$vowels = ['a','e','i','o','u'];
	$doubledConsonants = ['b','g','m','n','p','t'];

	if( substr( $verbStem, -1 ) == 'e' )
	{
		if( substr( $verbStem, -2, 1 ) != 'e' )
		{
			$verbStem = substr( $verbStem, 0, strlen( $verbStem ) - 1 );
		}
	}

	// Last character is a doubled consonant
	if( in_array( substr( $verbStem, -1 ), $doubledConsonants ) )
	{
		// ... but not because we stripped off a trailing 'e'
		if( substr( $originalVerbStem, -1 ) != 'e' )
		{
			// Next-to-last is a vowel
			if( in_array( substr( $verbStem, -2, 1 ), $vowels ) )
			{
				// ...and so is the one before that, so let's not double
				if( in_array( substr( $verbStem, -3, 1 ), $vowels ) )
				{

				}
				else
				{
					$verbStem .= substr( $verbStem, -1 );
				}

				// Exceptions, of course
				if( substr( $verbStem, -1 ) == 'n' )
				{
					if( substr( $verbStem, -2, 1 ) == 'e' )
					{
						$verbStem = substr( $verbStem, 0, strlen( $verbStem ) - 1 );
					}
				}
			}
		}
	}

	$verb = "{$verbStem}ing";

	/* Initials */
	$initialsNoun = '';
	for( $ch = 0; $ch < strlen( $noun ); $ch++ )
	{
		$initialsNoun .= $noun[$ch];

		if( $ch > 0 && in_array( $noun[$ch], $vowels ) )
		{
			break;
		}
	}
	$initialsNoun = ucfirst( $initialsNoun );

	$initialsVerb = '';
	for( $ch = 0; $ch < strlen( $verb ); $ch++ )
	{
		$initialsVerb .= $verb[$ch];

		if( $ch > 0 && in_array( $verb[$ch], $vowels ) )
		{
			break;
		}
	}
	$initialsVerb = ucfirst( $initialsVerb );
	$initials = $initialsNoun . $initialsVerb;

	/* Build the tweet */
	$tweet = sprintf( '%s “National %s %s Month” #Na%sMo', $body, ucwords( $noun ), ucwords( $verb ), $initials );

	if( $this->getOptionValue( 'no-tweet' ) )
	{
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
