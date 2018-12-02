<?php

namespace MediaWiki\Extension\LDAPAuthorization;

use MediaWiki\Extension\LDAPAuthorization\Requirement\ExcludedGroups;
use MediaWiki\Extension\LDAPAuthorization\Requirement\RequiredGroups;
use MediaWiki\Extension\LDAPAuthorization\Requirement\MatchAttributes;

class RequirementsChecker {

	/**
	 *
	 * @var \MediaWiki\Extension\LDAPProvider\Client
	 */
	protected $ldapClient = null;

	/**
	 *
	 * @var \Config
	 */
	protected $domainConfig = null;

	/**
	 *
	 * @var IRequirement[]
	 */
	protected $requirements = [];

	/**
	 *
	 * @param \MediaWiki\Extension\LDAPProvider\Client $ldapClient
	 * @param \Config $domainConfig
	 */
	public function __construct( $ldapClient, $domainConfig ) {
		$this->ldapClient = $ldapClient;
		$this->domainConfig = $domainConfig;
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

		foreach ( $this->requirements as $requirement ) {
			if ( !$requirement->isSatisfied() ) {
				return false;
			}
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
			$this->requirements[] = new RequiredGroups(
				$groupRules[Config::RULES_GROUPS_REQUIRED ],
				$groupDNs
			);
		}
		if ( !empty( $groupRules[Config::RULES_GROUPS_EXCLUDED ] ) ) {
			$this->requirements[] = new ExcludedGroups(
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
			$this->requirements[] = new MatchAttributes( $attributeRule, $userInfo );
		}
	}
}
