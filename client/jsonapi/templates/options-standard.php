<?php

$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'index' );
$config = $this->config( 'client/jsonapi/url/config', [] );

$resources = [];
$default = [
	'currency' => $this->param( 'currency' ),
	'locale' => $this->param( 'locale' ),
	'id' => ''
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
