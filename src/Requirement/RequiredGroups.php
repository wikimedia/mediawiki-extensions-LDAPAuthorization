<?php

namespace MediaWiki\Extension\LDAPAuthorization\Requirement;

use MediaWiki\Extension\LDAPAuthorization\IRequirement;

class RequiredGroups implements IRequirement {

	/**
	 *
	 * @var array
	 */
	protected $requiredGroups = [];

	/**
	 *
	 * @var array
	 */
	protected $groupDNs = [];

	/**
	 *
	 * @param array $requiredGroups
	 * @param array $groupDNs
	 */
	public function __construct( $requiredGroups, $groupDNs ) {
		$this->requiredGroups = array_map( 'strtolower', $requiredGroups );
		$this->groupDNs = array_map( 'strtolower', $groupDNs );

	}

	/**
	 *
	 * @return boolean
	 */
	public function isSatisfied() {
		foreach( $this->requiredGroups as $requiredGroup ) {
			//One matching group is sufficient! This is the same behavior as
			//the old "Extension:LdapAuthentication" by Ryan Lane
			if( in_array( $requiredGroup, $this->groupDNs ) ) {
				return true;
			}
		}
		return false;
	}
}