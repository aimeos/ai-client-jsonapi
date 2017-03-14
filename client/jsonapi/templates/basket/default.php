<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'get' );
$config = $this->config( 'client/jsonapi/url/config', array() );


$view = $this;
$enc = $this->encoder();


$ref = array( 'id', 'resource', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

if( !isset( $params['id'] ) ) {
	$params['id'] = '';
}


$fields = $this->param( 'fields', array() );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item ) use ( $fields, $view, $target, $cntl, $action, $config )
{
	$id = $item->getId();
	$attributes = $item->toArray();
	$params = array( 'resource' => 'basket', 'id' => $id );
	$allow = array( 'DELETE', 'GET', 'PATCH', 'POST', 'PUT' );

	if( ( $filter = $view->param( 'filter', [] ) ) !== [] ) {
		$params['filter'] = $filter;
	}

	if( $id !== null ) {
		$allow = array( 'GET' );
	}

	if( isset( $fields['basket'] ) ) {
		$attributes = array_intersect_key( $attributes, $fields['basket'] );
	}

	$entry = array(
		'id' => $id,
		'type' => 'basket',
		'links' => array(
			'self' => array(
				'href' => $view->url( $target, $cntl, $action, $params, array(), $config ),
				'allow' => $allow,
			),
		),
		'attributes' => $attributes,
	);

	return $entry;
};


?>
{
	"meta": {
		"total": <?php echo $this->get( 'total', 0 ); ?>

	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>

		"data": <?php echo json_encode( $entryFcn( $this->items ) ); ?>

	<?php endif; ?>

}
