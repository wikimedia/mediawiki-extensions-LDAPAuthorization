<?php

namespace MediaWiki\Extension\LDAPAuthorization\Requirement;

use MediaWiki\Extension\LDAPAuthorization\IRequirement;
use Throwable;

/**
 * A requirement that checks if the user matches a given LDAP query.
 */
class LdapQuery implements IRequirement {

	/**
	 * @var \MediaWiki\Extension\LDAPProvider\Client
	 */
	protected $ldapClient = null;

	/**
	 * @var string
	 */
	protected $userdn = null;

	/**
	 * @var string
	 */
	protected $query = null;

	/**
	 * @param \MediaWiki\Extension\LDAPProvider\Client $ldapClient
	 * @param string $userdn
	 * @param string $query
	 */
	public function __construct( $ldapClient, $userdn, $query ) {
		$this->ldapClient = $ldapClient;
		$this->userdn = $userdn;
		$this->query = $query;
	}

	/**
	 * @return bool
	 */
	public function isSatisfied() {
		try {
			$entries = $this->ldapClient->search(
				"(" . $this->query . ")",
				$this->userdn,
				[ "dn" ]
			);
		} catch ( Throwable $e ) {
			# For example a malformed query in the configuration.
			wfDebugLog(
				"LDAPAuthorization", "Could not check user against LDAP query: " . $e->getMessage()
			);
			return false;
		}

		return $entries["count"] > 0;
	}
}
