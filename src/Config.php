<?php

namespace MediaWiki\Extension\LDAPAuthorization;

class Config {
	public const DOMAINCONFIG_SECTION = 'authorization';
	public const RULES = 'rules';
	public const RULES_GROUPS = 'groups';
	public const RULES_GROUPS_REQUIRED = 'required';
	public const RULES_GROUPS_EXCLUDED = 'excluded';
	public const RULES_ATTRIBUTES = 'attributes';
	public const RULES_QUERY = 'query';
}
