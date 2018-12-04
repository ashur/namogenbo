<?php

use Huxtable\Core\File;

$pathBase		= __DIR__;
$pathLib		= $pathBase . '/lib';
$pathApp		= $pathLib  . '/NaMoGenBo';
$pathVendor		= $pathBase . '/vendor';
$pathData		= getenv( 'NAMOGENBO_DATA' );

if( $pathData == false )
{
	echo 'Error: Missing required environment variable NAMOGENBO_DATA' . PHP_EOL;
	exit( 1 );
}

/*
 * App configuration
 */
$info = require_once( $pathLib . '/info.php' );

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
$dirData = new File\Directory( $pathData );
$dirLib = $dirApp->childDir( 'lib' );

/*
 * Bot configuration
 */
$dirData = new File\Directory( $pathData );

/* Bot */
$bot = new NaMoGenBo\Bot( 'namogenbo', $dirData );

/*
 * Corpora configuration
 */
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

echo $tweet . PHP_EOL;
