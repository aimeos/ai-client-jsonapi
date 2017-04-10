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

$resources = [];
$default = [
	'currency' => $this->param( 'currency' ),
	'locale' => $this->param( 'locale' ),
];

foreach( $this->get( 'resources', [] ) as $resource ) {
	$resources[$resource] = $this->url( $target, $cntl, $action, ['resource' => $resource] + $default, [], $config );
}


?>
{
	"meta": {
		"prefix": <?php echo json_encode( $this->get( 'prefix' ) ); ?>,
		"resources": <?php echo json_encode( $resources ); ?>

	}

	<?php if( isset( $this->errors ) ) : ?>

		, "errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>
}
