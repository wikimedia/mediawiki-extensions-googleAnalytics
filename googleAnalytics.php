<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Google Analytics Integration',
	'version' => '3.0.0',
	'author' => array( 'Tim Laqua', '[https://www.mediawiki.org/wiki/User:DavisNT Davis Mosenkovs]' ),
	'descriptionmsg' => 'googleanalytics-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Google_Analytics_Integration',
);

$wgMessagesDirs['googleAnalytics'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['googleAnalytics'] = __DIR__ . '/googleAnalytics.i18n.php';

/*** Default configuration ***/

// Google Universal Analytics account id (e.g. "UA-12345678-1")
$wgGoogleAnalyticsAccount = '';

// HTML code for other web analytics (can be used along with Google Universal Analytics)
$wgGoogleAnalyticsOtherCode = '';

// Array with NUMERIC namespace IDs where web analytics code should NOT be included.
$wgGoogleAnalyticsIgnoreNsIDs = array();

// Array with page names (see magic word {{FULLPAGENAME}}) where web analytics code should NOT be included.
$wgGoogleAnalyticsIgnorePages = array();

// Array with special pages where web analytics code should NOT be included.
$wgGoogleAnalyticsIgnoreSpecials = array( 'Userlogin', 'Userlogout', 'Preferences', 'ChangePassword' );

/* WARNING! The following options were removed in version 3.0:
 *   $wgGoogleAnalyticsAddASAC
 *   $wgGoogleAnalyticsIgnoreSysops
 *   $wgGoogleAnalyticsIgnoreBots
 * It is possible (and advised) to use 'noanalytics' permission to exclude specific groups from web analytics. */

/*****************************/


$wgHooks['SkinAfterBottomScripts'][] = 'wfUniversalAnalyticsIntegrationSABS';

function wfUniversalAnalyticsIntegrationSABS( $skin, &$text = '' ) {
	global $wgGoogleAnalyticsAccount, $wgGoogleAnalyticsOtherCode, $wgGoogleAnalyticsIgnoreNsIDs,
			$wgGoogleAnalyticsIgnorePages, $wgGoogleAnalyticsIgnoreSpecials;

	if ( $skin->getUser()->isAllowed( 'noanalytics' ) ) {
		$text .= "<!-- Web analytics code inclusion is disabled for this user. -->\r\n";
		return true;
	}

	if ( count( array_filter( $wgGoogleAnalyticsIgnoreSpecials, function ( $v ) use ( $skin ) {
			return $skin->getTitle()->isSpecial( $v );
		} ) ) > 0
	  || in_array( $skin->getTitle()->getNamespace(), $wgGoogleAnalyticsIgnoreNsIDs, true )
	  || in_array( $skin->getTitle()->getPrefixedText(), $wgGoogleAnalyticsIgnorePages, true ) ) {
		$text .= "<!-- Web analytics code inclusion is disabled for this page. -->\r\n";
		return true;
	}

	$appended = false;

	if ( $wgGoogleAnalyticsAccount !== '' ) {
		$text .= <<<EOD
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '
EOD
. $wgGoogleAnalyticsAccount . <<<EOD
', 'auto');
  ga('send', 'pageview');

</script>

EOD;
		$appended = true;
	}

	if ( $wgGoogleAnalyticsOtherCode !== '' ) {
		$text .= $wgGoogleAnalyticsOtherCode . "\r\n";
		$appended = true;
	}

	if ( !$appended ) {
		$text .= "<!-- No web analytics configured. -->\r\n";
	}

	return true;
}
