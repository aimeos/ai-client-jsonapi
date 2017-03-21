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


$entryFcn = function( \Aimeos\MShop\Order\Item\Base\Product\Iface $item, $position = null ) use ( $fields, $view, $target, $cntl, $action, $config )
{
	$allow = array( 'GET' );
	$attributes = $item->toArray();
	$id = $view->param( 'id', 'default' );

	if( ( $relid = $item->getId() ) === null )
	{
		$allow = array( 'DELETE', 'GET', 'PATCH', 'POST' );
		$relid = $position;
	}

	if( isset( $fields['basket/product'] ) ) {
		$attributes = array_intersect_key( $attributes, $fields['basket/product'] );
	}

	$params = array( 'resource' => 'basket', 'id' => $id, 'related' => 'product', 'relatedid' => $relid );

	$entry = array(
		'id' => $id,
		'type' => 'basket/product',
		'links' => array(
			'self' => array(
				'href' => $view->url( $target, $cntl, $action, $params, array(), $config ),
				'allow' => $allow,
			),
		),
		'attributes' => $attributes,
	);

	foreach( $item->getProducts() as $orderProduct )
	{
		$productEntry = $orderProduct->toArray();

		foreach( $orderProduct->getProducts() as $subProduct ) {
			$productEntry['products'][] = $subProduct->toArray();
		}
		$entry['attributes']['products'][] = $productEntry;
	}

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

		<?php
			$data = array();
			$items = $this->get( 'items', array() );

			if( is_array( $items ) )
			{
				foreach( $items as $pos => $orderProductItem ) {
					$data[] = $entryFcn( $orderProductItem, $pos );
				}
			}
			else
			{
				$data = $entryFcn( $items );
			}
		?>

		"data": <?php echo json_encode( $data ); ?>

	<?php endif; ?>

}
