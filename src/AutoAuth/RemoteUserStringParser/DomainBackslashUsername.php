<?php

namespace MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser;

use InvalidArgumentException;
use MediaWiki\Extension\LDAPAuthorization\AutoAuth\SimpleDomainUserDescription;

class DomainBackslashUsername extends Base {

	/**
	 * @param string $remoteUserString
	 * @return \MediaWiki\Extension\LDAPAuthorization\AutoAuth\IDomainUserDescription
	 * @throws InvalidArgumentException
	 */
	public function parse( $remoteUserString ) {
		if ( strpos( $remoteUserString, '\\' ) === false ) {
			throw new InvalidArgumentException( "Unsupported format!" );
		}
		$parts = explode( '\\', $remoteUserString );
		$desc = new SimpleDomainUserDescription( $parts[1], $parts[0] );

		return $desc;
	}

}
