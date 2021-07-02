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
	 * @param string $operator Expected '&' or '|'
	 * @param mixed $values Values to check if user is authorized
	 * @return bool
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

			if ( ( $operator == '&' ) && ( !$result ) ) {
				return false;
			}

			if ( ( $operator == '|' ) && ( $result ) ) {
				return true;
			}

		}

		if ( $operator == '&' ) {
			return true;
		} else {
			// $opertor == '|'
			return false;
		}
	}

	/**
	 * This function will take in an attribute and one or more
	 * values that the user must match to be authorized.
	 *
	 * @param string $attribute LDAP result to compare with
	 * @param string|array $allowedValues Values LDAP must be equal to
	 * @return bool
	 */
	private function evaluateAttr( $attribute, $allowedValues ) {
		if ( !array_key_exists( $attribute, $this->attributes ) ) {
			return false;
		}

		if ( is_array( $this->attributes[$attribute] ) ) {
			$value = $this->attributes[$attribute][0];
		} else {
			$value = $this->attributes[$attribute];
		}

		if ( !is_array( $allowedValues ) ) {
			$allowedValues = [ $allowedValues ];
		}

		foreach ( $allowedValues as $allowedValue ) {

			if ( $value == $allowedValue ) {
				return true;
			}

		}

		return false;
	}
}
