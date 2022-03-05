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
		'src',
	],
	'i18n' => [
		'client/jsonapi' => 'i18n',
	],
	'template' => [
		'client/jsonapi/templates' => [
			'templates/client/jsonapi',
		],
	],
];
