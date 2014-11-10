<?php

/*
 * Copyright (c) 2014 The MITRE Corporation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

class LDAPAuthorization {

	/**
	* This function will accept a user and a boolean.  The function
	* will then work with the other functions in this class to determine
	* if the user is authorized or not.
	*
	* @param User $user: This is an instance of the MediaWiki user variable
	* @param &$authorized: Boolean variable to be returned true or false
	*	depending on whether user is authorized or not
	*/
	public static function authorize( User $user, &$authorized ) {

		if ( strlen( $user->mName ) == 0 ) {
			$authorized = false;
			return $authorized;
		}

		$instance = new self( $user->mName );

		$valid = $instance->setConfiguration(
			$GLOBALS['LDAPAuthorization_ServerName'],
			$GLOBALS['LDAPAuthorization_ServerPort'],
			$GLOBALS['LDAPAuthorization_UseTLS'],
			$GLOBALS['LDAPAuthorization_SearchString'],
			$GLOBALS['LDAPAuthorization_Filter'],
			$GLOBALS['LDAPAuthorization_Rules']
		);

		if ( ! $valid ) {
			$authorized = false;
			return $authorized;
		}

		$valid = $instance->getEntries();

		if ( ! $valid ) {
			$authorized = false;
			return $authorized;
		}

		$authorized = $instance->evaluate();

		return $authorized;

	}

	/**
	* @var $user: internal variable for user
	* @var $serverName: internal variable for server name
	* @var $serverPort: internal variable for server port
	* @var $useTLS: internal variable for using secure connection
	* @var $searchstring: internal variable to query LDAP server
	* @var $filter: internal variable for filtering LDAP results
	* @var $entries: internal variable for LDAP results
	* @var $rules: internal variable for predetermined LDAP rules
	*	to determine who is authorized and who is not.
	*/
	private $user;
	private $serverName;
	private $serverPort;
	private $useTLS;
	private $searchString;
	private $filter;
	private $entries;
	private $rules;

	/**
	* This function is used to set the passed in user to the internal
	* user variable.
	*
	* @param $user: User passed in through Mediawiki hook
	*/
	public function __construct( $user ) {
		$this->user = $user;
	}

	/**
	* This function will make sure there are global variables set in
	* LocalSettings.php or PostConfiguration.php.  It then sets each
	* global variable to their local counterparts.
	*/
	public function setConfiguration() {

		if ( ! isset( $GLOBALS['LDAPAuthorization_ServerName'] ) ) {
			return false;
		}
		$this->serverName = $GLOBALS['LDAPAuthorization_ServerName'];

		if ( ! isset( $GLOBALS['LDAPAuthorization_ServerPort'] ) ) {
			return false;
		}
		$this->serverPort = $GLOBALS['LDAPAuthorization_ServerPort'];

		if ( ! isset( $GLOBALS['LDAPAuthorization_UseTLS'] ) ) {
			return false;
		}
		$this->useTLS = $GLOBALS['LDAPAuthorization_UseTLS'];

		if ( ! isset( $GLOBALS['LDAPAuthorization_SearchString'] ) ) {
			return false;
		}
		$this->searchString = $GLOBALS['LDAPAuthorization_SearchString'];

		if ( ! isset( $GLOBALS['LDAPAuthorization_Filter'] ) ) {
			return false;
		}
		$this->filter = $GLOBALS['LDAPAuthorization_Filter'];

		if ( ! isset( $GLOBALS['LDAPAuthorization_Rules'] ) ) {
			return false;
		}
		$this->rules = $GLOBALS['LDAPAuthorization_Rules'];

		return true;

	}

	/**
	* This function will use the internal variables for the LDAP server
	* to connect with the server and query with it based off the user.
	*/
	public function getEntries() {

		$ldapconn = ldap_connect( $this->serverName, $this->serverPort );

		if ( $ldapconn == false ) {
			return false;
		}

		ldap_set_option( $ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3 );
		ldap_set_option( $ldapconn, LDAP_OPT_REFERRALS, 0 );

		if ( $this->useTLS ) {
			ldap_start_tls( $ldapconn );
		}

		$filter = str_replace( "USERNAME", $this->user, $this->filter );

		$result = @ldap_search( $ldapconn, $this->searchString, $filter );

		if ( $result == false ) {
			ldap_unbind( $ldapconn );
			return false;
		}

		$entries = ldap_get_entries( $ldapconn, $result );

		ldap_unbind( $ldapconn );

		if ( $entries == array() ) {
			return false;
		}

		$this->entries = $entries[0];

		return true;
	}

	/**
	* This function is the top level evaluation function.  It
	* will look at the beginning of the rules array and call
	* evaluateExpr to perform the rest of the authorized
	* calculation.
	*/
	public function evaluate() {

		return $this->evaluateExpr( '&', $this->rules );

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

		if ( ! is_array( $values ) || ( $values == array() ) ) {
			return false;
		}

		foreach ( $values as $key => $value ) {

			if ( ( $key == '&' ) || ( $key == '|' ) ) {
				$result = $this->evaluateExpr( $key, $value );
			} else {
				$result = $this->evaluateAttr( $key, $value );
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
		} else { // $opertor == '|'
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

		if ( ! array_key_exists( $attribute, $this->entries ) ) {
			return false;
		}

		$value = $this->entries[$attribute][0];

		if ( ! is_array( $allowedValues ) ) {
			$allowedValues = array ( $allowedValues );
		}

		foreach ( $allowedValues as $allowedValue ) {

			if ( $value == $allowedValue ) {
				return true;
			}

		}

		return false;
	}
}
