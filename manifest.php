<?php

return array(
	'name' => 'ai-client-jsonapi',
	'depends' => array(
		'aimeos-core',
	),
	'include' => array(
		'client/jsonapi/src',
	),
	'i18n' => array(
		'client' => 'client/i18n',
	),
	'custom' => array(
		'client/jsonapi/templates' => array(
			'client/jsonapi/templates',
		),
	),
);
