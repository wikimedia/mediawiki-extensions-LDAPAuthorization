<?php

namespace MediaWiki\Extension\LDAPAuthorization\Tests\Requirement;

use MediaWiki\Extension\LDAPAuthorization\Requirement\MatchAttributes;

class MatchAttributesTest extends \PHPUnit\Framework\TestCase {

	/**
	 *
	 * @param array $ruledefinition
	 * @param array $attribs
	 * @param bool $expected
	 * @covers MediaWiki\Extension\LDAPAuthorization\Requirement\MatchAttributes::isSatisfied
	 * @dataProvider provideData
	 */
	public function testIsSatisfied( $ruledefinition, $attribs, $expected ) {
		$requirement = new MatchAttributes( $ruledefinition, $attribs );
		$result = $requirement->isSatisfied();

		$this->assertEquals( $expected, $result );
	}

	protected const RULE_1 = [
			"&" => [
					"status" => "active",
					"|" => [
							"department" => [
									"100",
									"200"
							],
							"level" => [
									"5",
									"6"
							]
					]
			]
	];

	protected const RULE_2 = [
			"status" => "active",
			"|" => [
					"department" => [
							"100",
							"200"
					],
					"level" => [
							"5",
							"6"
					]
			]
	];

	public static function provideData() {
		return [
			'example1-from-mworg-positive' => [
				self::RULE_1,
				[
					'status' => [ 'active' ],
					'department' => [ 200 ]
				],
				true
			],
			'example2-from-mworg-positive' => [
				self::RULE_2,
				[
					'status' => [ 'active' ],
					'department' => [ 500 ],
					'level' => [ 5 ]
				],
				true
			],
			'example1-from-mworg-negative' => [
				self::RULE_1,
				[
					'status' => [ 'inactive' ],
					'department' => [ 200 ],
					'level' => [ 7 ]
				],
				false
			],
			'example2-from-mworg-negative' => [
				self::RULE_2,
				[
					'status' => [ 'active' ],
					'level' => [ 7 ]
				],
				false
			],
			'example1-from-mworg-multi-dept-member' => [
				self::RULE_1,
				[
					'status' => [ 'active' ],
					'department' => [ 200, 201 ],
					'level' => [ 6 ]
				],
				true
			],
			'ucs-flag-negative-1' => [
				[ "applicationActivated" => "TRUE" ],
				[ "applicationActivated" => "true" ],
				false
			],
			'ucs-flag-negative-2' => [
				[ "applicationActivated" => "TRUE" ],
				[ "applicationActivated" => 'FALSE' ],
				false
			],
			'ucs-flag-positive-1' => [
				[ "applicationActivated" => "TRUE" ],
				[ "applicationActivated" => 'TRUE' ],
				true
			],
			'T280873-case-insensitivity' => [
				[
					'&' => [
						'orgLOKOGeactiveerd' => true,
						'orgLOKORechtAlias' => 'cn=Kennisdatabank,ou=rights,ou=accounts,dc=loko,dc=be'
					]
				],
				[
					'orglokogeactiveerd' => [ true ],
					'orglokorechtalias' => 'cn=Kennisdatabank,ou=rights,ou=accounts,dc=loko,dc=be'
				],
				true
			],
			'T280873-case-insensitivity-inverse' => [
				[
					'&' => [
						'orglokogeactiveerd' => true,
						'orglokorechtalias' => 'cn=Kennisdatabank,ou=rights,ou=accounts,dc=loko,dc=be'
					]
				],
				[
					'orgLOKOGeactiveerd' => [ true ],
					'orgLOKORechtAlias' => 'cn=Kennisdatabank,ou=rights,ou=accounts,dc=loko,dc=be'
				],
				true
			],
			'T280873-case-insensitivity-mixed' => [
				[
					'&' => [
						'orglokogeactiveerd' => true,
						'orgLOKORechtAlias' => 'cn=Kennisdatabank,ou=rights,ou=accounts,dc=loko,dc=be'
					]
				],
				[
					'orgLOKOGeactiveerd' => [ true ],
					'orglokorechtalias' => 'cn=Kennisdatabank,ou=rights,ou=accounts,dc=loko,dc=be'
				],
				true
			],
		];
	}
}
