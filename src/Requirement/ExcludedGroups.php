<?php

namespace MediaWiki\Extension\LDAPAuthorization\Requirement;

use MediaWiki\Extension\LDAPAuthorization\IRequirement;

class ExcludedGroups implements IRequirement {

	/**
	 * @var array
	 */
	protected $excludedGroups = [];

	/**
	 * @var array
	 */
	protected $groupDNs = [];

	/**
	 * @param array $excludedGroups
	 * @param array $groupDNs
	 */
	public function __construct( $excludedGroups, $groupDNs ) {
		$this->excludedGroups = array_map( 'strtolower', $excludedGroups );
		$this->groupDNs = array_map( 'strtolower', $groupDNs );
	}

	/**
	 * @return bool
	 */
	public function isSatisfied() {
		foreach ( $this->excludedGroups as $excludedGroup ) {
			if ( in_array( $excludedGroup, $this->groupDNs ) ) {
				return false;
			}
		}
		return true;
	}
}
