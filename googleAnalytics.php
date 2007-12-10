<?php
if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['other'][] = array(
	'name'=>'Google Analytics Integration',
	'url'=>'http://www.mediawiki.org/wiki/Extension:Google_Analytics_Integration',
	'author'=>'Tim Laqua',
	'description'=>'Inserts Google Analytics script (urchin.js) in to MediaWiki pages for tracking.',
	'version'=>'1.2',
);

if( version_compare( $wgVersion, '1.11alpha', '>=' ) ) {
    $wgHooks['SkinAfterBottomScripts'][]  = 'efGoogleAnalyticsHookText';
} else {
	$wgHooks['MonoBookTemplateToolboxEnd'][]  = 'efGoogleAnalyticsHookEcho';
	$wgHooks['BeforePageDisplay'][]  = 'efGoogleAnalyticsHookOut';
}

function efGoogleAnalyticsHookText(&$skin, &$text='') {
	$text .= efAddGoogleAnalytics();
	return true;
}

function efGoogleAnalyticsHookEcho(&$out) {
	global $googleAnalyticsMonobook;
	if ($googleAnalyticsMonobook)
		echo(efAddGoogleAnalytics());
	return true;
}

function efGoogleAnalyticsHookOut(&$out) {
	global $googleAnalyticsMonobook;
	if (!$googleAnalyticsMonobook)
		$out->addHTML(efAddGoogleAnalytics());
	return true;
}

function efAddGoogleAnalytics() {
    global $googleAnalytics, $wgUser;
    if (!$wgUser->isAllowed('bot')) {
        if (!$wgUser->isAllowed('protect')) {
            if ($googleAnalytics) {
                $funcOutput = "\n<script src=\"http://www.google-analytics.com/urchin.js\" type=\"text/javascript\">\n</script>\n".
                              "<script type=\"text/javascript\">\n".
                              "_uacct = "."\"" . $googleAnalytics . "\";\n".
                              "urchinTracker();\n".
                              "</script>\n";
            } else {
                    $funcOutput = "\n<!-- Set \$googleAnalytics to your uacct # provided by Google Analytics. -->";
            }
        } else {
            $funcOutput = "\n<!-- Google Analytics tracking is disabled for users with 'protect' rights (I.E. sysops) -->";
        }
    } else {
        $funcOutput = "\n<!-- Google Analytics tracking is disabled for bots -->";
    }

	return $funcOutput;
}

///Alias for efAddGoogleAnalytics - backwards compatibility.
function addGoogleAnalytics() { return efAddGoogleAnalytics(); }
