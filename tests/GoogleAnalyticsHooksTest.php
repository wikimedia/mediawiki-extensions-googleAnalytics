<?php

/**
 * @covers GoogleAnalyticsHooks
 */
class GoogleAnalyticsHooksTest extends MediaWikiLangTestCase {
	public function setUp() : void {
		parent::setUp();
		$this->setMwGlobals( 'wgGoogleAnalyticsAccount', '' );
	}

	/**
	 * @param bool $allowed
	 * @param string $title
	 * @return Skin
	 */
	private function mockSkin( $allowed, $title = 'Main Page' ) {
		$skin = $this->getMockBuilder( 'SkinFallback' )
			->disableOriginalConstructor()
			->setMethods( [ 'getUser', 'getTitle' ] )
			->getMock();
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->setMethods( [ 'isAllowed' ] )
			->getMock();

		$user->expects( $this->any() )
			->method( 'isAllowed' )
			->will( $this->returnValue( $allowed ) );
		$skin
			->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( $title ) ) );

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
		$this->setMwGlobals( 'wgGoogleAnalyticsAccount', 'foobarbaz' );
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'www.google-analytics.com/analytics.js', $text );
		$this->assertStringContainsString( 'foobarbaz', $text );
		$this->setMwGlobals( 'wgGoogleAnalyticsAccount', '' );
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'No web analytics configured', $text );
		$this->setMwGlobals( 'wgGoogleAnalyticsOtherCode', 'analytics.example.com/foo.js' );
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'analytics.example.com/foo.js', $text );
	}

	public function testAnonymizeIp() {
		$this->setMwGlobals( 'wgGoogleAnalyticsAccount', 'foobarbaz' );
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringContainsString( 'anonymizeIp', $text );
		$this->setMwGlobals( 'wgGoogleAnalyticsAnonymizeIP', false );
		$text = '';
		GoogleAnalyticsHooks::onSkinAfterBottomScripts( $this->mockSkin( false ), $text );
		$this->assertStringNotContainsString( 'anonymizeIp', $text );
	}

	/**
	 * @dataProvider provideExcludedPages
	 */
	public function testExcludedPages( $type, $conf, $title, $include ) {
		$this->setMwGlobals( $type, [ $conf ] );
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
			[ 'wgGoogleAnalyticsIgnoreSpecials', 'Preferences', 'Special:Preferences', false ],
			[ 'wgGoogleAnalyticsIgnoreSpecials', 'Userlogout', 'Special:Preferences', true ],
			[ 'wgGoogleAnalyticsIgnoreNsIDs', NS_HELP, 'Help:FooBar', false ],
			[ 'wgGoogleAnalyticsIgnoreNsIDs', NS_MAIN, 'Help:FooBar', true ],
			[ 'wgGoogleAnalyticsIgnorePages', 'Help:FooBar', 'Help:FooBar', false ],
			[ 'wgGoogleAnalyticsIgnorePages', 'Help:FooBar', 'Help:FooBarBaz', true ],
		];
	}
}
