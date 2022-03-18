<?php
namespace MediaWiki\Extension\LDAPAuthorization\Hook;

use GlobalVarConfig;
use MediaWiki\Extension\LDAPAuthorization\AutoAuth\IRemoteUserStringParser;
use MediaWiki\Extension\LDAPAuthorization\Config;
use MediaWiki\Extension\LDAPAuthorization\RequirementsChecker;
use MediaWiki\Extension\LDAPProvider\ClientConfig;
use MediaWiki\Extension\LDAPProvider\ClientFactory;
use MediaWiki\Extension\LDAPProvider\DomainConfigFactory;
use MediaWiki\Logger\LoggerFactory;
use MWException;

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
	 * @param \Config $config
	 * @param string &$username
	 */
	public function __construct( $config, &$username ) {
		$this->config = $config;
		$this->username =& $username;

		$this->logger = LoggerFactory::getInstance( 'LDAPAuthorization' );
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

		$this->logger->debug( __CLASS__ . ": Check authorization for user '{$this->username}'." );
		try {
			$desc = $parser->parse( $this->username );
			$domain = $desc->getDomain();
			$username = $desc->getUsername();
			$this->logger->debug( "Resolved to username '{$username}' and domain '$domain'" );

			$ldapClient = ClientFactory::getInstance()->getForDomain( $domain );
			$domainConfig = DomainConfigFactory::getInstance()->factory(
				$domain, Config::DOMAINCONFIG_SECTION
			);

			$requirementsChecker = new RequirementsChecker( $ldapClient, $domainConfig );
			$requirementsChecker->setLogger( $this->logger );
			if ( !$requirementsChecker->allSatisfiedBy( $username ) ) {
				$this->logger->debug( 'Requirements could not be satisfied.' );
				$this->username = '';
				return false;
			}
			$this->username = $username;
			$this->logger->debug( 'All requirements satisfied.' );

			$result = $ldapClient->getUserInfo( $this->username );
			$usernameAttributeName = $ldapClient->getConfig( ClientConfig::USERINFO_USERNAME_ATTR );
			if ( isset( $result[$usernameAttributeName] ) ) {
				$this->username = $result[$usernameAttributeName];
				$this->logger->debug( "Set new username '{$this->username}' from LDAP user info." );
			}

		} catch ( MWException $ex ) {
			$this->logger->error( "Could not check login requirements for {$this->username}" );
			$this->logger->error( $ex->getMessage() );
			$this->username = '';
			return false;
		}

		/**
		 * This is a feature after updating wikis which used strtolower on usernames.
		 * to use it, set this in LocalSettings.php:
		 * $LDAPAuthentication2UsernameNormalizer = 'strtolower';
		 */
		$normalizer = $this->config->get( 'AutoAuthUsernameNormalizer' );
		if ( !empty( $normalizer ) && is_callable( $normalizer ) ) {
			$this->username = call_user_func( $normalizer, $this->username );
			$this->logger->debug( "Normalized username to '{$this->username}'." );
		}

		return true;
	}
}
