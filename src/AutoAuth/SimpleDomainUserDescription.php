<?php

namespace MediaWiki\Extension\LDAPAuthorization\AutoAuth;

class SimpleDomainUserDescription implements IDomainUserDescription {

	/**
	 *
	 * @var string
	 */
	private $username = '';

	/**
	 *
	 * @var string
	 */
	private $domain = '';

	/**
	 *
	 * @param string $username
	 * @param string $domain
	 */
	public function __construct( $username, $domain ) {
		$this->username = $username;
		$this->domain = $domain;
	}

	/**
	 *
	 * @return string
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 *
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

}
