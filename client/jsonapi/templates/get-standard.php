<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/options/target' );
$cntl = $this->config( 'client/jsonapi/url/options/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/options/action', 'options' );
$config = $this->config( 'client/jsonapi/url/options/config', [] );

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