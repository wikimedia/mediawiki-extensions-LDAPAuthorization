<?php

namespace MediaWiki\Extension\LDAPAuthorization\Tests\AutoAuth\RemoteUserStringParser;

use InvalidArgumentException;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser\DomainBackslashUsername;

class DomainBackslashUsernameTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser\DomainBackslashUsername::parse
	 */
	public function testParse() {
		$config = new HashConfig( [] );
		$parser = new DomainBackslashUsername( $config );
		$desc = $parser->parse( "ABC\\Some_user" );

		$this->assertInstanceOf(
			'MediaWiki\\Extension\\LDAPAuthorization\\AutoAuth\\IDomainUserDescription',
			$desc
		);

		$this->assertEquals( 'ABC', $desc->getDomain() );
		$this->assertEquals( 'Some_user', $desc->getUsername() );
	}

	/**
	 * @covers MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser\DomainBackslashUsername::parse
	 */
	public function testException() {
		$config = new HashConfig( [] );
		$parser = new DomainBackslashUsername( $config );
		$this->expectException( InvalidArgumentException::class );
		$desc = $parser->parse( "Some_user@ABC" );
	}
}
