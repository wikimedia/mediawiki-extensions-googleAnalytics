<?php

use MediaWiki\Title\Title;

/**
 * @covers GoogleAnalyticsHooks
 */
class GoogleAnalyticsHooksTest extends MediaWikiLangTestCase {
	public function setUp(): void {
		parent::setUp();
		$this->overrideConfigValue( 'GoogleAnalyticsAccount', '' );
	}

	/**
	 * @param bool $allowed
	 * @param string $title
	 * @return Skin
	 */
	private function mockSkin( $allowed, $title = 'Main Page' ) {
		$skin = $this->getMockBuilder( 'SkinFallback' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getUser', 'getTitle' ] )
			->getMock();
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'isAllowed' ] )
			->getMock();

		$user->expects( $this->any() )
			->method( 'isAllowed' )
			->willReturn( $allowed );
		$skin
			->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( Title::newFromText( $title ) );

		return $skin;
	}

	/**
	 * @dataProvider provideUserPermissions
	 */
	public function testUserPermissions( $allowed, $expected ) {
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( $allowed ), $text );
		$this->assertStringContainsString( $expected, $text );
	}

	public static function provideUserPermissions() {
		return [
			[ false, 'No web analytics configured' ],
			[ true, 'Web analytics code inclusion is disabled for this user' ],
		];
	}

	public function testAccountIdSet() {
		$this->overrideConfigValue( 'GoogleAnalyticsAccount', 'foobarbaz' );
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'www.google-analytics.com/analytics.js', $text );
		$this->assertStringContainsString( 'foobarbaz', $text );
		$this->overrideConfigValue( 'GoogleAnalyticsAccount', '' );
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'No web analytics configured', $text );
		$this->overrideConfigValue( 'GoogleAnalyticsOtherCode', 'analytics.example.com/foo.js' );
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'analytics.example.com/foo.js', $text );
	}

	public function testAnonymizeIp() {
		$this->overrideConfigValue( 'GoogleAnalyticsAccount', 'foobarbaz' );
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'anonymizeIp', $text );
		$this->overrideConfigValue( 'GoogleAnalyticsAnonymizeIP', false );
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringNotContainsString( 'anonymizeIp', $text );
	}

	/**
	 * @dataProvider provideExcludedPages
	 */
	public function testExcludedPages( $type, $conf, $title, $include ) {
		$this->overrideConfigValue( $type, [ $conf ] );
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false, $title ), $text );
		if ( $include ) {
			$this->assertStringContainsString( 'No web analytics configured', $text );
		} else {
			$this->assertStringContainsString( 'Web analytics code inclusion is disabled for this page', $text );
		}
	}

	public static function provideExcludedPages() {
		return [
			[ 'GoogleAnalyticsIgnoreSpecials', 'Preferences', 'Special:Preferences', false ],
			[ 'GoogleAnalyticsIgnoreSpecials', 'Userlogout', 'Special:Preferences', true ],
			[ 'GoogleAnalyticsIgnoreNsIDs', NS_HELP, 'Help:FooBar', false ],
			[ 'GoogleAnalyticsIgnoreNsIDs', NS_MAIN, 'Help:FooBar', true ],
			[ 'GoogleAnalyticsIgnorePages', 'Help:FooBar', 'Help:FooBar', false ],
			[ 'GoogleAnalyticsIgnorePages', 'Help:FooBar', 'Help:FooBarBaz', true ],
		];
	}
}
