<?php

return array(
	'name' => 'ai-client-jsonapi',
	'depends' => array(
		'aimeos-core',
		'ai-controller-frontend',
	),
	'include' => array(
		'client/jsonapi/src',
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
