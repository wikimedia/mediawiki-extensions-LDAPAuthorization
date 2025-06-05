<?php

namespace MediaWiki\Extension\LDAPAuthorization\Tests\AutoAuth\RemoteUserStringParser;

use InvalidArgumentException;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser\UsernameAtDomain;

class UsernameAtDomainTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser\DomainBackslashUsername::parse
	 */
	public function testParse() {
		$config = new HashConfig( [] );
		$parser = new UsernameAtDomain( $config );
		$desc = $parser->parse( "Some_user@ABC" );

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
		$parser = new UsernameAtDomain( $config );
		$this->expectException( InvalidArgumentException::class );
		$desc = $parser->parse( "ABC\\Some_user" );
	}
}
