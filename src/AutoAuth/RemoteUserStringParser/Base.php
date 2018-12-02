<?php

namespace MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser;

use MediaWiki\Extension\LDAPAuthorization\AutoAuth\IRemoteUserStringParser;

abstract class Base implements IRemoteUserStringParser {

	/**
	 *
	 * @var \Config
	 */
	private $config = null;

	/**
	 *
	 * @param \config $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 *
	 * @param \Config $config
	 * @return IRemoteUserStringParser
	 */
	public static function factory( $config ) {
		return new static( $config );
	}
}
