<?php

namespace MediaWiki\Extension\LDAPAuthorization\Hook;

use MediaWiki\Config\Config as MediaWikiConfig;
use MediaWiki\Extension\LDAPAuthorization\Config;
use MediaWiki\Extension\LDAPAuthorization\RequirementsChecker;
use MediaWiki\Extension\LDAPProvider\ClientFactory;
use MediaWiki\Extension\LDAPProvider\DomainConfigFactory;
use MediaWiki\Extension\LDAPProvider\UserDomainStore;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use Psr\Log\LoggerInterface;

class PluggableAuthUserAuthorization {

	/**
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var bool
	 */
	protected $authorized = false;

	/**
	 * @var \MediaWiki\Extension\LDAPProvider\Client
	 */
	protected $ldapClient = null;

	/**
	 * @var MediaWikiConfig
	 */
	protected $domainConfig = null;

	/**
	 * @var string
	 */
	protected $domain = '';

	/** @var LoggerInterface */
	protected $logger = null;

	/**
	 * @param User $user
	 * @param bool &$authorized
	 */
	public function __construct( $user, &$authorized ) {
		$this->user = $user;
		$this->authorized =& $authorized;
		$this->logger = LoggerFactory::getInstance( 'LDAPAuthorization' );

		$this->initDomain();
		$this->logger->debug( "Domain set to '{$this->domain}'." );
		if ( $this->domain !== null ) {
			$this->ldapClient = ClientFactory::getInstance()->getForDomain( $this->domain );
			$this->domainConfig = DomainConfigFactory::getInstance()->factory(
				$this->domain, Config::DOMAINCONFIG_SECTION
			);
		}
	}

	/**
	 * @param User $user
	 * @param bool &$authorized
	 * @return bool
	 */
	public static function callback( $user, &$authorized ) {
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		$handler = new static( $user, $authorized );
		return $handler->process();
	}

	/**
	 * @return bool
	 */
	public function process() {
		$this->logger->debug( __CLASS__ . ": Check authorization for user '{$this->user->getName()}'." );
		if ( $this->isLocalUser() ) {
			$this->logger->debug( 'Skipping local user.' );
			return true;
		}
		$requirementsChecker = new RequirementsChecker( $this->ldapClient, $this->domainConfig );
		$requirementsChecker->setLogger( $this->logger );
		if ( !$requirementsChecker->allSatisfiedBy( $this->user->getName() ) ) {
			$this->logger->debug( 'Requirements could not be satisfied.' );
			$this->authorized = false;
			return false;
		}
		$this->logger->debug( 'All requirements satisfied.' );

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
		$authManager = MediaWikiServices::getInstance()->getAuthManager();
		$domain = $authManager->getAuthenticationSessionData(
			\MediaWiki\Extension\LDAPAuthentication2\PluggableAuth::DOMAIN_SESSION_KEY
		);
		if ( $domain === null ) {
			$this->logger->debug( 'No domain found for user in session.' );
			return false;
		}
		if ( $domain === \MediaWiki\Extension\LDAPAuthentication2\PluggableAuth::DOMAIN_VALUE_LOCAL ) {
			$this->logger->debug( 'Domain `local` chosen.' );
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
			$this->logger->debug( 'No domain found for user in database.' );
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
