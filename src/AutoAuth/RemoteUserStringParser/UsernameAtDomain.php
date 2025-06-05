<?php

namespace MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser;

use InvalidArgumentException;
use MediaWiki\Extension\LDAPAuthorization\AutoAuth\SimpleDomainUserDescription;

class UsernameAtDomain extends Base {

	/**
	 * @param string $remoteUserString
	 * @return \MediaWiki\Extension\LDAPAuthorization\AutoAuth\IDomainUserDescription
	 * @throws InvalidArgumentException
	 */
	public function parse( $remoteUserString ) {
		if ( strpos( $remoteUserString, '@' ) === false ) {
			throw new InvalidArgumentException( "Unsupported format!" );
		}
		$parts = explode( '@', $remoteUserString );
		$desc = new SimpleDomainUserDescription( $parts[0], $parts[1] );

		return $desc;
	}

}
