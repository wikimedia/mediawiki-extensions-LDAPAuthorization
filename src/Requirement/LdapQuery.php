<?php

namespace MediaWiki\Extension\LDAPAuthorization\Requirement;

use MediaWiki\Extension\LDAPAuthorization\IRequirement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * A requirement that checks if the user matches a given LDAP query.
 */
class LdapQuery implements IRequirement, LoggerAwareInterface {

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
	 * @var LoggerInterface
	 */
	protected $logger = null;

	/**
	 * @param \MediaWiki\Extension\LDAPProvider\Client $ldapClient
	 * @param string $userdn
	 * @param string $query
	 */
	public function __construct( $ldapClient, $userdn, $query ) {
		$this->ldapClient = $ldapClient;
		$this->userdn = $userdn;
		$this->query = $query;
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
		try {
			$entries = $this->ldapClient->search(
				"(" . $this->query . ")",
				$this->userdn,
				[ "dn" ]
			);
		} catch ( Throwable $e ) {
			# For example a malformed query in the configuration.
			$this->logger->error(
				'Could not check user against LDAP query {query}: {message}',
				[ 'query' => $this->query, 'message' => $e->getMessage() ]
			);
			return false;
		}

		$satisfied = $entries["count"] > 0;
		$this->logger->debug(
			'LdapQuery {query} for userdn {userdn}: {result} ({count} result(s)).',
			[
				'query' => $this->query,
				'userdn' => $this->userdn,
				'result' => $satisfied ? 'satisfied' : 'not satisfied',
				'count' => $entries['count'],
			]
		);
		return $satisfied;
	}
}
