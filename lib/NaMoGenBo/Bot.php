<?php

/*
 * This file is part of NaMoGenBo
 */
namespace NaMoGenBo;

use Huxtable\Bot\Corpora;
use Huxtable\Core\Config;
use Huxtable\Core\File;
use Huxtable\Core\Utils;

class Bot extends \Huxtable\Bot\Bot
{
	use \Huxtable\Bot\Corpora\Consumer;
	use \Huxtable\Bot\History\Consumer;
	use \Huxtable\Bot\Twitter\Consumer;

	/**
	 * @var	Huxtable\Bot\Corpora\Corpora
	 */
	protected $corpora;

	/**
	 * @return	string
	 */
	public function getTweet()
	{
		$vowels = ['a','e','i','o','u'];

		/*
		 * Noun and Verb
		 */
		do
		{
			$nounIsAcceptable = true;

			$noun = $this->corpora->getItem( 'nouns', ['words','objects','animals'] );
			$noun = strtolower( $noun );

			$nounFirstLetter = strtolower( substr( $noun, 0, 1 ) );

			$nounIsAcceptable = $nounIsAcceptable && !in_array( $nounFirstLetter, $vowels );
			$nounIsAcceptable = $nounIsAcceptable && substr( $noun, -3 ) != 'ing';
		}
		while( !$nounIsAcceptable );

		$verbData = $this->corpora->getItem( 'verbs', 'all', 'verbs' );
		$verbStem = $verbData['present'];
		$originalVerbStem = $verbStem;

		/* Conjugate the verb, badly */
		$vowels[] = 'y';
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
						// ...
					}
					else
					{
						$verbStem .= substr( $verbStem, -1, 1 );
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

		/*
		 * Abbreviation
		 */
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
		$abbreviation = $initialsNoun . $initialsVerb;

		/*
		 * Event
		 */
		$event = sprintf( '“National %s %s Month”', ucwords( $noun ), ucwords( $verb ) );

		/*
		 * Build the tweet
		 */
		$hashtag = "#Na{$abbreviation}Mo";
		$tweet = $this->corpora->getItem( 'tweets', 'bodies' );

		$tweet = str_replace( '{event}', $event, $tweet, $eventReplacements );
		if( $eventReplacements == 0 )
		{
			$tweet = sprintf( '%s %s', $tweet, $event );
		}

		$tweet = str_replace( '{hashtag}', $hashtag, $tweet, $hashtagReplacements );
		if( $hashtagReplacements == 0 )
		{
			$tweet = sprintf( '%s %s', $tweet, $hashtag );
		}

		$month = $this->corpora->getItem( 'time', 'months' );
		$tweet = str_replace( '{month}', $month, $tweet );

		$tweet = str_replace( '{number}', rand( 2, 24 ), $tweet );

		return $tweet;
	}

	/**
	 * @param	Huxtable\Core\File\Directory	$dirCorpora
	 */
	public function setCorporaDirectory( File\Directory $dirCorpora )
	{
		$this->corpora = new Corpora\Corpora( $dirCorpora, $this->getHistoryObject() );
	}
}
