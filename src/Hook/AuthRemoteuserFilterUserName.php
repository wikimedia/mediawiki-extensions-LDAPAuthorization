<?php
namespace MediaWiki\Extension\LDAPAuthorization\Hook;

use MediaWiki\Extension\LDAPProvider\ClientFactory;
use MediaWiki\Extension\LDAPAuthorization\RequirementsChecker;
use MediaWiki\Extension\LDAPAuthorization\Config;
use MWException;
use MediaWiki\Extension\LDAPAuthorization\AutoAuth\IRemoteUserStringParser;
use GlobalVarConfig;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Extension\LDAPProvider\DomainConfigFactory;

/**
 * In conjunction with "Extension:Auth_remoteuser" we need to make sure that
 * only authorized users are being logged on automatically
 */
class AuthRemoteuserFilterUserName {

	/**
	 *
	 * @var string
	 */
	protected $username = '';

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger = null;

	/**
	 *
	 * @var \BagOStuff
	 */
	protected $cache = null;

	/**
	 *
	 * @param \Config $config
	 * @param string &$username
	 */
	public function __construct( $config, &$username ) {
		$this->config = $config;
		$this->username =& $username;

		$this->logger = LoggerFactory::getInstance( 'LDAPAuthorization' );
		// TODO: Even though LPAPProvider/Client uses a cache for UserGroupsRequests,
		// we should have an own cache here
		$this->cache = wfGetMainCache();
	}

	/**
	 *
	 * @param string &$username
	 * @return bool
	 */
	public static function callback( &$username ) {
		$config = new GlobalVarConfig( 'LDAPAuthorization' );
		$handler = new static( $config, $username );

		return $handler->process();
	}

	/**
	 *
	 * @return bool
	 */
	public function process() {
		$remoteUserStringParserKey = $this->config->get( 'AutoAuthRemoteUserStringParser' );
		$remoteUserStringParserReg = $this->config->get( 'AutoAuthRemoteUserStringParserRegistry' );

		if ( !isset( $remoteUserStringParserReg[$remoteUserStringParserKey] ) ) {
			throw new MWException( "No factory callback for "
				. "'$remoteUserStringParserKey' available!" );
		}

		$factoryCallback = $remoteUserStringParserReg[$remoteUserStringParserKey];
		$parser = call_user_func_array( $factoryCallback, [ $this->config ] );

		if ( $parser instanceof IRemoteUserStringParser === false ) {
			throw new MWException( "Factory callback for "
				. "'$remoteUserStringParserKey' did not return an `IRemoteUserStringParser` "
				. "object!" );
		}

		try {
			$desc = $parser->parse( $this->username );
			$domain = $desc->getDomain();
			$ldapClient = ClientFactory::getInstance()->getForDomain( $domain );
			$domainConfig = DomainConfigFactory::getInstance()->factory(
				$domain, Config::DOMAINCONFIG_SECTION
			);

			$requirementsChecker = new RequirementsChecker( $ldapClient, $domainConfig );
			if ( !$requirementsChecker->allSatisfiedBy( $this->username ) ) {
				$this->username = '';
				return false;
			}
			$this->username = $desc->getUsername();
		} catch ( MWException $ex ) {
			$this->logger->error( "Could not check login requirements for {$this->username}" );
		}

		return true;
	}
}
