<?php

use Huxtable\Core\File;

$pathBase		= __DIR__;
$pathLib		= $pathBase . '/lib';
$pathApp		= $pathLib  . '/NaMoGenBo';
$pathVendor		= $pathBase . '/vendor';

/*
 * Initialize autoloading
 */
include_once( $pathApp . '/Autoloader.php' );
NaMoGenBo\Autoloader::register();

include_once( $pathVendor . '/huxtable/bot/autoload.php' );
include_once( $pathVendor . '/huxtable/core/autoload.php' );

/*
 * Some basics
 */
$dirApp = new File\Directory( $pathBase );
$dirLib = $dirApp->childDir( 'lib' );

/*
 * Bot configuration
 */
$bot = new NaMoGenBo\Bot( 'namogenbo' );

$dirCorpora = $dirApp
	->childDir( 'lib' )
	->childDir( 'corpora' );

$bot->setCorporaDirectory( $dirCorpora );

/*
 * Ensure that we don't tweet the most egregious stuff
 */
$exclusions = json_decode( file_get_contents( "{$pathLib}/data/exclude.json" ), true );

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

/*
 * Generate the tweet
 */
echo $tweet . PHP_EOL;
