<?php

namespace MediaWiki\Extension\LDAPAuthorization\Requirement;

use MediaWiki\Extension\LDAPAuthorization\IRequirement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RequiredGroups implements IRequirement, LoggerAwareInterface {

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
	 * @var LoggerInterface
	 */
	protected $logger = null;

	/**
	 *
	 * @param array $requiredGroups
	 * @param array $groupDNs
	 */
	public function __construct( $requiredGroups, $groupDNs ) {
		$this->requiredGroups = array_map( 'strtolower', $requiredGroups );
		$this->groupDNs = array_map( 'strtolower', $groupDNs );
		$this->logger = new NullLogger();
	}

	/**
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 *
	 * @return bool
	 */
	public function isSatisfied() {
		foreach ( $this->requiredGroups as $requiredGroup ) {
			// One matching group is sufficient! This is the same behavior as
			// the old "Extension:LdapAuthentication" by Ryan Lane
			if ( in_array( $requiredGroup, $this->groupDNs ) ) {
				$this->logger->debug( 'Required group {group} found.', [ 'group' => $requiredGroup ] );
				return true;
			}
		}
		$this->logger->debug(
			"None of the required groups found. Required: [ '{required}' ]. User groups: [ '{groups}' ].",
			[
				'required' => implode( "', '", $this->requiredGroups ),
				'groups' => implode( "', '", $this->groupDNs ),
			]
		);
		return false;
	}
}
