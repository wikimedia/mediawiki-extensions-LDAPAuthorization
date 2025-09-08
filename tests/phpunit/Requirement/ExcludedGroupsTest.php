<?php

namespace MediaWiki\Extension\LDAPAuthorization\Tests\Requirement;

use MediaWiki\Extension\LDAPAuthorization\Requirement\ExcludedGroups;

class ExcludedGroupsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param array $excludedGroups
	 * @param array $groups
	 * @param bool $expected
	 * @covers MediaWiki\Extension\LDAPAuthorization\Requirement\ExcludedGroups::isSatisfied
	 * @dataProvider provideData
	 */
	public function testIsSatisfied( $excludedGroups, $groups, $expected ) {
		$requirement = new ExcludedGroups( $excludedGroups, $groups );
		$result = $requirement->isSatisfied();

		$this->assertEquals( $expected, $result );
	}

	public static function provideData() {
		return [
			'positive' => [
				[ 'X', 'Y' ],
				[ 'A', 'C', 'D' ],
				true
			],
			'negative' => [
				[ 'A', 'D' ],
				[ 'A', 'B', 'C' ],
				false
			]
		];
	}
}
