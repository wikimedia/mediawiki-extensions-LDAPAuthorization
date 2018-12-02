<?php

namespace MediaWiki\Extension\LDAPAuthorization\AutoAuth\RemoteUserStringParser;

use MediaWiki\Extension\LDAPAuthorization\AutoAuth\SimpleDomainUserDescription;
use MWException;

class UsernameAtDomain extends Base {

	/**
	 *
	 * @param string $remoteUserString
	 * @return \MediaWiki\Extension\LDAPAuthorization\AutoAuth\IDomainUserDescription
	 */
	public function parse( $remoteUserString ) {
		if ( strpos( $remoteUserString, '@' ) === false ) {
			throw new MWException( "Unsupported format!" );
		}
		$parts = explode( '@', $remoteUserString );
		$desc = new SimpleDomainUserDescription( $parts[0], $parts[1] );

		return $desc;
	}

}
