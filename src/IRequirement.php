<?php

namespace MediaWiki\Extension\LDAPAuthorization;

interface IRequirement {

	/**
	 *
	 * @return boolean
	 */
	public function isSatisfied();
}
