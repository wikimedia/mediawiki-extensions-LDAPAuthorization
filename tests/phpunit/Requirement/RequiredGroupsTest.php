<?php

namespace MediaWiki\Extension\LDAPAuthorization\Tests\Requirement;

use MediaWiki\Extension\LDAPAuthorization\Requirement\RequiredGroups;

class RequiredGroupsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param array $requiredGroups
	 * @param array $groups
	 * @param bool $expected
	 * @covers MediaWiki\Extension\LDAPAuthorization\Requirement\RequiredGroups::isSatisfied
	 * @dataProvider provideData
	 */
	public function testIsSatisfied( $requiredGroups, $groups, $expected ) {
		$requirement = new RequiredGroups( $requiredGroups, $groups );
		$result = $requirement->isSatisfied();

		$this->assertEquals( $expected, $result );
	}

	public static function provideData() {
		return [
			'positive' => [
				[ 'A', 'B' ],
				[ 'A', 'b', 'C' ],
				true
			],
			'positive-with-only-one-group' => [
				[ 'A', 'B' ],
				[ 'a', 'C' ],
				true
			],
			'negative' => [
				[ 'X', 'B' ],
				[ 'A', 'C', 'D' ],
				false
			]
		];
	}
}
