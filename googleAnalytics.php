<?php
if ( !defined( 'MEDIAWIKI' ) ) {
        die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}
 
$wgExtensionCredits['parserhook'][] = array(
    'name'=>'Google Analytics Extension',
    'url'=>'http://www.mediawiki.org/wiki/Extension:Google_Analytics_Integration',
    'author'=>'Tim Laqua, t.laqua at gmail dot com',
    'description'=>'Inserts Google Analytics script (urchin.js) in to MediaWiki pages for tracking.',
    'version'=>'1.0'
);
 
if (!$googleAnalyticsSkinHack) {
    if ($googleAnalyticsMonobook) {
        $wgHooks['MonoBookTemplateToolboxEnd'][]  = 'googleAnalyticsHook'; 
    } else {
        $wgHooks['BeforePageDisplay'][]  = 'googleAnalyticsHook'; 
    }
}
 
function googleAnalyticsHook(&$out) {
    global $googleAnalyticsMonobook;
    if ($googleAnalyticsMonobook) {
        echo(addGoogleAnalytics()); 
    } else {
        $out->addHTML(addGoogleAnalytics()); 
    }
    return true;
}
 
function addGoogleAnalytics() {
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
