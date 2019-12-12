<?php

return array(
	'name' => 'ai-client-jsonapi',
	'depends' => array(
		'aimeos-core',
		'ai-controller-frontend',
	),
	'config' => array(
		'config',
	),
	'include' => array(
		'client/jsonapi/src',
		'lib/custom/src',
	),
	'i18n' => array(
		'client/jsonapi' => 'client/jsonapi/i18n',
	),
	'custom' => array(
		'client/jsonapi/templates' => array(
			'client/jsonapi/templates',
		),
	),
);
