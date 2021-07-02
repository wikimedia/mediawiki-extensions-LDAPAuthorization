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

	protected $rule1 = [
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

	protected $rule2 = [
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

	public function provideData() {
		return [
			'example1-from-mworg-positive' => [
				$this->rule1,
				[
					'status' => [ 'active' ],
					'department' => [ 200 ]
				],
				true
			],
			'example2-from-mworg-positive' => [
				$this->rule2,
				[
					'status' => [ 'active' ],
					'department' => [ 500 ],
					'level' => [ 5 ]
				],
				true
			],
			'example1-from-mworg-negative' => [
				$this->rule1,
				[
					'status' => [ 'inactive' ],
					'department' => [ 200 ],
					'level' => [ 7 ]
				],
				false
			],
			'example2-from-mworg-negative' => [
				$this->rule2,
				[
					'status' => [ 'active' ],
					'level' => [ 7 ]
				],
				false
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
			'T280875-multivalue-attributes' => [
				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => 'value2'
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'value1', 'value2' ]
				],
				false
			],
			'T280875-multivalue-attributes-1' => [

				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => 'value2'
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'value1' ]
				],
				false
			],
			'T280875-multivalue-attributes-2' => [
				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'value1', 'value2' ]
				],
				true
			],
			'T280875-multivalue-attributes-3' => [
				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1', 'value3' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'value1', 'value2' ]
				],
				true
			],
			'T280875-multivalue-attributes-9' => [
				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1', 'value3' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'value3', 'value1', 'value2' ]
				],
				true
			],
			'T280875-multivalue-attributes-4' => [
				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'value3', 'value1', 'value2' ]
				],
				false
			],
			'T280875-multivalue-attributes-5' => [
				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1', 'random' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'value3', 'value1', 'value2' ]
				],
				false
			],
			'T280875-multivalue-attributes-6' => [
				[
					'&' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1', 'random' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => 'value3'
				],
				false
			],
			'T280875-multivalue-attributes-7' => [
				[
					'|' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1', 'random' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => 'value3'
				],
				false
			],
			'T280875-multivalue-attributes-8' => [
				[
					'|' => [
						'SOMEMULTIVALUEATTRIBUTE' => [ 'value2', 'value1', 'random' ]
					]
				],
				[
					'SOMEMULTIVALUEATTRIBUTE' => [ 'random', 'value2' ]
				],
				true
			],
		];
	}
}
