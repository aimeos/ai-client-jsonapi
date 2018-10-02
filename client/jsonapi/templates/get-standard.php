<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'options' );
$config = $this->config( 'client/jsonapi/url/config', [] );

$details = 'This is the Aimeos JSON REST API

Use the HTTP OPTIONS method to retrieve a list available resources from ' . $this->url( $target, $cntl, $action, [], [], $config ) . '
Documentation about he Aimeos JSON REST API is available at https://aimeos.org/docs/Developers/Client/JSONAPI';

?>
{
	"errors": {
		"title": "Use OPTIONS method for the resource list",
		"detail": <?= json_encode( $details ); ?>

	}
}