<?php

namespace MediaWiki\Extension\LDAPAuthorization\Tests\Requirement;

use MediaWiki\Extension\LDAPAuthorization\Requirement\MatchAttributes;

class MatchAttributesTest extends \PHPUnit\Framework\TestCase {

	/**
	 *
	 * @param array $ruledefinition
	 * @param array $attribs
	 * @param boolean $expected
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
			]
		];
	}
}
