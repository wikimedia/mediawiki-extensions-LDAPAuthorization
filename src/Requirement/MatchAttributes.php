<?php

namespace MediaWiki\Extension\LDAPAuthorization\Requirement;

use MediaWiki\Extension\LDAPAuthorization\IRequirement;

class MatchAttributes implements IRequirement {

	/**
	 *
	 * @var array
	 */
	protected $matchingRule = [];

	/**
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 *
	 * @param array $matchingRule
	 * @param array $attributes
	 */
	public function __construct( $matchingRule, $attributes ) {
		$this->matchingRule = $matchingRule;
		$this->attributes = $attributes;
	}

	/**
	 * This function is the top level evaluation function.  It
	 * will look at the beginning of the rules array and call
	 * evaluateExpr to perform the rest of the authorized
	 * calculation.
	 *
	 * @return bool
	 */
	public function isSatisfied() {
		$this->normalizeAttributeKeys();
		return $this->evaluateExpr( '&', $this->matchingRule );
	}

	private function normalizeAttributeKeys() {
		$normalAttributes = [];
		foreach ( $this->attributes as $key => $val ) {
			$normalKey = strtolower( $key );
			$normalAttributes[$normalKey] = $val;
		}

		$this->attributes = $normalAttributes;
	}

	/**
	* This function will take in an operator and an array of values
	* to check to determine whether the user is authorized or not.
	*
	* @param $operator: Expected '&' or '|'
	* @param $values: Values to check if user is authorized
	*/
	private function evaluateExpr( $operator, $values ) {
		if ( ( $operator != '&' ) && ( $operator != '|' ) ) {
			return false;
		}

		if ( !is_array( $values ) || ( $values == [] ) ) {
			return false;
		}

		foreach ( $values as $key => $value ) {

			if ( ( $key == '&' ) || ( $key == '|' ) ) {
				$result = $this->evaluateExpr( $key, $value );
			} else {
				$normalKey = strtolower( $key );
				$result = $this->evaluateAttr( $normalKey, $value );
			}

			if ( ( $operator == '&' ) && ( ! $result ) ) {
				return false;
			}

			if ( ( $operator == '|' ) && ( $result ) ) {
				return true;
			}

		}

		if ( $operator == '&' ) {
			return true;
		} else {
			// $operator == '|'
			return false;
		}
	}

	/**
	* This function will take in an attribute and one or more
	* values that the user must match to be authorized.
	*
	* @param $attribute: LDAP result to compare with
	* @param $allowedValues: Values LDAP must be equal to
	*/
	private function evaluateAttr( $attribute, $allowedValues ) {
		if ( ! array_key_exists( $attribute, $this->attributes ) ) {
			return false;
		}

		$values = $this->attributes[$attribute];
		if ( !is_array( $values ) ) {
			$values = [ $values ];
		}

		if ( ! is_array( $allowedValues ) ) {
			$allowedValues = [ $allowedValues ];
		}

		$difference = array_diff( $values, $allowedValues );

		return empty( $difference );
	}
}
