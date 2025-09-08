<?php

namespace MediaWiki\Extension\LDAPAuthorization\AutoAuth;

interface IRemoteUserStringParser {

	/**
	 * @param string $remoteUserString
	 * @return IDomainUserDescription
	 */
	public function parse( $remoteUserString );
}
