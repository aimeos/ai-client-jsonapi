<?php

return [
	'name' => 'ai-client-jsonapi',
	'depends' => [
		'aimeos-core',
		'ai-controller-frontend',
	],
	'config' => [
		'config',
	],
	'include' => [
		'client/jsonapi/src',
		'lib/custom/src',
	],
	'i18n' => [
		'client/jsonapi' => 'client/jsonapi/i18n',
	],
	'template' => [
		'client/jsonapi/templates' => [
			'client/jsonapi/templates',
		],
	],
];
