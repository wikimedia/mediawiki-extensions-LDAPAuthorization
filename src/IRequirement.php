<?php

namespace MediaWiki\Extension\LDAPAuthorization;

interface IRequirement {

	/**
	 *
	 * @return bool
	 */
	public function isSatisfied();
}
