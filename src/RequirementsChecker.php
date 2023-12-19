<?php

namespace MediaWiki\Extension\LDAPAuthorization;

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
	 * @var \Config
	 */
	protected $domainConfig = null;

	/**
	 * @var IRequirement[]
	 */
	protected $requirements = [];

	/** @var LoggerInterface */
	protected $logger = null;

	/**
	 *
	 * @param \MediaWiki\Extension\LDAPProvider\Client $ldapClient
	 * @param \Config $domainConfig
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

		if ( isset( $rules[Config::RULES_GROUPS] ) ) {
			$this->makeGroupRequirements( $username, $rules[Config::RULES_GROUPS] );
		}
		if ( isset( $rules[Config::RULES_ATTRIBUTES] ) ) {
			$this->makeMatchAttributesRequirement( $username, $rules[Config::RULES_ATTRIBUTES] );
		}
		if ( isset( $rules[Config::RULES_QUERY] ) ) {
			$this->makeLdapQueryRequirement( $username, $rules[Config::RULES_QUERY] );
		}

		foreach ( $this->requirements as $key => $requirement ) {
			if ( !$requirement->isSatisfied() ) {
				$this->logger->debug( "Requirement '$key' not satisfied." );
				return false;
			}
			$this->logger->debug( "Requirement '$key' satisfied." );
		}

		return true;
	}

	/**
	 *
	 * @param string $username
	 * @param array $groupRules
	 * @return void
	 */
	protected function makeGroupRequirements( $username, $groupRules ) {
		$ldapUserGroups = $this->ldapClient->getUserGroups( $username );
		$groupDNs = $ldapUserGroups->getFullDNs();

		if ( !empty( $groupRules[Config::RULES_GROUPS_REQUIRED ] ) ) {
			$this->requirements['groups.required'] = new RequiredGroups(
				$groupRules[Config::RULES_GROUPS_REQUIRED ],
				$groupDNs
			);
		}
		if ( !empty( $groupRules[Config::RULES_GROUPS_EXCLUDED ] ) ) {
			$this->requirements['groups.excluded'] = new ExcludedGroups(
				$groupRules[Config::RULES_GROUPS_EXCLUDED ],
				$groupDNs
			);
		}
	}

	/**
	 *
	 * @param string $username
	 * @param array $attributeRule
	 * @return void
	 */
	protected function makeMatchAttributesRequirement( $username, $attributeRule ) {
		if ( !empty( $attributeRule ) ) {
			$userInfo = $this->ldapClient->getUserInfo( $username );
			$this->requirements['attributes'] = new MatchAttributes( $attributeRule, $userInfo );
		}
	}

	/**
	 *
	 * @param string $username
	 * @param string $query
	 * @return void
	 */
	protected function makeLdapQueryRequirement( $username, $query ) {
		if ( !empty( $query ) ) {
			$userdn = $this->ldapClient->getUserInfo( $username )["dn"];
			$this->requirements['query'] = new LdapQuery( $this->ldapClient, $userdn, $query );
		}
	}
}
