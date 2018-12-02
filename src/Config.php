<?php

namespace MediaWiki\Extension\LDAPAuthorization;

class Config {
	const DOMAINCONFIG_SECTION = 'authorization';
	const RULES = 'rules';
	const RULES_GROUPS = 'groups';
	const RULES_GROUPS_REQUIRED = 'required';
	const RULES_GROUPS_EXCLUDED = 'excluded';
	const RULES_ATTRIBUTES = 'attributes';
}
