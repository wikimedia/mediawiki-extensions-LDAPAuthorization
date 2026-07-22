<?php

namespace MediaWiki\Extension\LDAPAuthorization;

use MediaWiki\Config\Config as MediaWikiConfig;
use MediaWiki\Extension\LDAPAuthorization\Requirement\ExcludedGroups;
use MediaWiki\Extension\LDAPAuthorization\Requirement\LdapQuery;
use MediaWiki\Extension\LDAPAuthorization\Requirement\MatchAttributes;
use MediaWiki\Extension\LDAPAuthorization\Requirement\RequiredGroups;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RequirementsChecker implements LoggerAwareInterface {

	/**
	 * @var \MediaWiki\Extension\LDAPProvider\Client
	 */
	protected $ldapClient = null;

	/**
	 * @var MediaWikiConfig
	 */
	protected $domainConfig = null;

	/**
	 * @var IRequirement[]
	 */
	protected $requirements = [];

	/** @var LoggerInterface */
	protected $logger = null;

	/**
	 * @param \MediaWiki\Extension\LDAPProvider\Client $ldapClient
	 * @param MediaWikiConfig $domainConfig
	 */
	public function __construct( $ldapClient, $domainConfig ) {
		$this->ldapClient = $ldapClient;
		$this->domainConfig = $domainConfig;
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
	 * @param string $username
	 * @return bool
	 */
	public function allSatisfiedBy( $username ) {
		$rules = $this->domainConfig->get( Config::RULES );

		try {
			if ( isset( $rules[Config::RULES_GROUPS] ) ) {
				$this->makeGroupRequirements( $username, $rules[Config::RULES_GROUPS] );
			}
			if ( isset( $rules[Config::RULES_ATTRIBUTES] ) ) {
				$this->makeMatchAttributesRequirement( $username, $rules[Config::RULES_ATTRIBUTES] );
			}
			if ( isset( $rules[Config::RULES_QUERY] ) ) {
				$this->makeLdapQueryRequirement( $username, $rules[Config::RULES_QUERY] );
			}
		} catch ( \Throwable $e ) {
			$this->logger->error(
				'Failed to build authorization requirements for {username}, {exception}: {message}',
				[
					'username' => $username,
					'exception' => get_class( $e ),
					'message' => $e->getMessage(),
				]
			);
			return false;
		}

		foreach ( $this->requirements as $key => $requirement ) {
			if ( !$requirement->isSatisfied() ) {
				$this->logger->debug( 'Requirement {key} not satisfied.', [ 'key' => $key ] );
				return false;
			}
			$this->logger->debug( 'Requirement {key} satisfied.', [ 'key' => $key ] );
		}

		return true;
	}

	/**
	 * @param string $username
	 * @param array $groupRules
	 * @return void
	 */
	protected function makeGroupRequirements( $username, $groupRules ) {
		$ldapUserGroups = $this->ldapClient->getUserGroups( $username );
		$groupDNs = $ldapUserGroups->getFullDNs();

		$this->logger->debug(
			'User {username} is member of {count} LDAP group(s).',
			[ 'username' => $username, 'count' => count( $groupDNs ) ]
		);

		if ( !empty( $groupRules[Config::RULES_GROUPS_REQUIRED ] ) ) {
			$req = new RequiredGroups(
				$groupRules[Config::RULES_GROUPS_REQUIRED ],
				$groupDNs
			);
			$req->setLogger( $this->logger );

			$this->requirements['groups.required'] = $req;
		}
		if ( !empty( $groupRules[Config::RULES_GROUPS_EXCLUDED ] ) ) {
			$req = new ExcludedGroups(
				$groupRules[Config::RULES_GROUPS_EXCLUDED ],
				$groupDNs
			);
			$req->setLogger( $this->logger );

			$this->requirements['groups.excluded'] = $req;
		}
	}

	/**
	 * @param string $username
	 * @param array $attributeRule
	 * @return void
	 */
	protected function makeMatchAttributesRequirement( $username, $attributeRule ) {
		if ( !empty( $attributeRule ) ) {
			$userInfo = $this->ldapClient->getUserInfo( $username );

			$req = new MatchAttributes( $attributeRule, $userInfo );
			$req->setLogger( $this->logger );

			$this->requirements['attributes'] = $req;
		}
	}

	/**
	 * @param string $username
	 * @param string $query
	 * @return void
	 */
	protected function makeLdapQueryRequirement( $username, $query ) {
		if ( !empty( $query ) ) {
			$userdn = $this->ldapClient->getUserInfo( $username )["dn"];

			$req = new LdapQuery( $this->ldapClient, $userdn, $query );
			$req->setLogger( $this->logger );

			$this->requirements['query'] = $req;
		}
	}
}
