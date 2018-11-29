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
	protected $config = null;

	/**
	 *
	 * @var IRequirement[]
	 */
	protected $requirements = [];

	/**
	 *
	 * @param \MediaWiki\Extension\LDAPProvider\Client $ldapClient
	 * @param \Config $config
	 */
	public function __construct( $ldapClient, $config ) {
		$this->ldapClient = $ldapClient;
		$this->config = $config;
	}

	/**
	 * @param \User $user
	 * @return boolean
	 */
	public function allSatisfiedBy( $user ) {
		$this->makeGroupRequirements( $user );
		$this->makeMatchAttributesRequirement( $user );

		foreach( $this->requirements as $requirement ) {
			if( !$requirement->isSatisfied() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 *
	 * @param \User $user
	 * @return void
	 */
	protected function makeGroupRequirements( $user ) {
		if( !$this->config->has( Config::RULES_GROUPS ) ) {
			return;
		}

		$ldapUserGroups = $this->ldapClient->getUserGroups( $user );
		$groupDNs = $ldapUserGroups->getFullDNs();

		$groups = $this->config->get( Config::RULES_GROUPS );
		if( isset( $groups[Config::RULES_GROUPS_REQUIRED ] ) ) {
			$this->requirements[] = new RequiredGroups(
				$groups[Config::RULES_GROUPS_REQUIRED ],
				$groupDNs
			);
		}
		if( isset( $groups[Config::RULES_GROUPS_EXCLUDED ] ) ) {
			$this->requirements[] = new ExcludedGroups(
				$groups[Config::RULES_GROUPS_EXCLUDED ],
				$groupDNs
			);
		}
	}

	/**
	 *
	 * @param \User $user
	 * @return void
	 */
	protected function makeMatchAttributesRequirement( $user ) {
		if( !$this->config->has( Config::RULES_ATTRIBUTES ) ) {
			return;
		}
		$matchRule = $this->config->get( Config::RULES_ATTRIBUTES );
		$userInfo = $this->ldapClient->getUserInfo( $user->getName() );

		$this->requirements[] = new MatchAttributes( $matchRule );
	}
}
