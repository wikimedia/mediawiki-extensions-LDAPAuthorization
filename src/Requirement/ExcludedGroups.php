<?php

namespace MediaWiki\Extension\LDAPAuthorization\Requirement;

use MediaWiki\Extension\LDAPAuthorization\IRequirement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ExcludedGroups implements IRequirement, LoggerAwareInterface {

	/**
	 * @var array
	 */
	protected $excludedGroups = [];

	/**
	 * @var array
	 */
	protected $groupDNs = [];

	/**
	 * @var LoggerInterface
	 */
	protected $logger = null;

	/**
	 * @param array $excludedGroups
	 * @param array $groupDNs
	 */
	public function __construct( $excludedGroups, $groupDNs ) {
		$this->excludedGroups = array_map( 'strtolower', $excludedGroups );
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
	 * @return bool
	 */
	public function isSatisfied() {
		foreach ( $this->excludedGroups as $excludedGroup ) {
			if ( in_array( $excludedGroup, $this->groupDNs ) ) {
				$this->logger->debug(
					'User is member of excluded group {group}.',
					[ 'group' => $excludedGroup ]
				);
				return false;
			}
		}
		return true;
	}
}
