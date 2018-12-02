<?php

namespace MediaWiki\Extension\LDAPAuthorization\Tests\AutoAuth;

use MediaWiki\Extension\LDAPAuthorization\AutoAuth\SimpleDomainUserDescription;

class SimpleDomainUserDescriptionTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers MediaWiki\Extension\LDAPAuthorization\AutoAuth\SimpleDomainUserDescription::__constructor
	 */
	public function testAll() {
		$desc = new SimpleDomainUserDescription( 'Some_user', 'ABC' );

		$this->assertInstanceOf(
			'MediaWiki\\Extension\\LDAPAuthorization\\AutoAuth\\SimpleDomainUserDescription',
			$desc
		);

		$this->assertEquals( 'ABC', $desc->getDomain() );
		$this->assertEquals( 'Some_user', $desc->getUsername() );
	}
}
