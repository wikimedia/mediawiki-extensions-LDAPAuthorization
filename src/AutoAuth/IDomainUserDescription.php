<?php

namespace MediaWiki\Extension\LDAPAuthorization\AutoAuth;

interface IDomainUserDescription {

	/**
	 * @return string
	 */
	public function getUsername();

	/**
	 * @return string
	 */
	public function getDomain();
}
