<?php

namespace MediaWiki\Extension\LDAPAuthorization\Hook;

use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\LDAPProvider\UserDomainStore;
use MediaWiki\Extension\LDAPProvider\ClientFactory;
use MediaWiki\Extension\LDAPProvider\DomainConfigFactory;
use MediaWiki\Extension\LDAPAuthorization\RequirementsChecker;
use MediaWiki\Extension\LDAPAuthorization\Config;
use MediaWiki\MediaWikiServices;

class PluggableAuthUserAuthorization {

	/**
	 *
	 * @var \User
	 */
	protected $user = null;

	/**
	 *
	 * @var boolean
	 */
	protected $authorized = false;

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
	 * @var string
	 */
	protected $domain = '';

	/**
	 *
	 * @param \User $user
	 * @param bool &$authorized
	 */
	public function __construct( $user, &$authorized ) {
		$this->user = $user;
		$this->authorized =& $authorized;

		$this->initDomain();
		if ( $this->domain !== null ) {
			$this->ldapClient = ClientFactory::getInstance()->getForDomain( $this->domain );
			$this->domainConfig = DomainConfigFactory::getInstance()->factory(
				$this->domain, Config::DOMAINCONFIG_SECTION
			);
		}
	}

	/**
	 *
	 * @param \User $user
	 * @param bool &$authorized
	 * @return bool
	 */
	public static function callback( $user, &$authorized ) {
		$handler = new static( $user, $authorized );
		return $handler->process();
	}

	/**
	 *
	 * @return bool
	 */
	public function process() {
		if ( $this->isLocalUser() ) {
			return true;
		}
		$requirementsChecker = new RequirementsChecker( $this->ldapClient, $this->domainConfig );
		if ( !$requirementsChecker->allSatisfiedBy( $this->user->getName() ) ) {
			$this->authorized = false;
			return false;
		}

		return true;
	}

	/**
	 * This hookhandler should not be invoked anyway if the user clicks on
	 * "Login" instead of "Login with PluggableAuth"
	 * @return bool
	 */
	protected function isLocalUser() {
		return $this->ldapClient === null;
	}

	protected function initDomain() {
		if ( !$this->initDomainFromAuthenticationSessionData() ) {
			if ( !$this->initDomainFromUserDomainStore() ) {
				$this->initDomainFromSettings();
			}
		}
	}

	protected function initDomainFromAuthenticationSessionData() {
		if ( !class_exists( '\MediaWiki\Extension\LDAPAuthentication2\PluggableAuth' ) ) {
			return false;
		}
		$domain = AuthManager::singleton()->getAuthenticationSessionData(
			\MediaWiki\Extension\LDAPAuthentication2\PluggableAuth::DOMAIN_SESSION_KEY
		);
		if ( $domain === null ) {
			return false;
		}
		if ( $domain === \MediaWiki\Extension\LDAPAuthentication2\ExtraLoginFields::DOMAIN_VALUE_LOCAL ) {
			$domain = null;
		}

		$this->domain = $domain;
		return true;
	}

	protected function initDomainFromUserDomainStore() {
		$userDomainStore = new UserDomainStore(
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);
		$domain = $userDomainStore->getDomainForUser( $this->user );

		if ( $domain === null ) {
			return false;
		}

		$this->domain = $domain;
		return true;
	}

	protected function initDomainFromSettings() {
		$configuredDomains = DomainConfigFactory::getInstance()->getConfiguredDomains();
		$this->domain = $configuredDomains[0];
	}
}
