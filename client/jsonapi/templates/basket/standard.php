<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'index' );
$config = $this->config( 'client/jsonapi/url/config', [] );

$enc = $this->encoder();


$ref = array( 'id', 'resource', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

if( !isset( $params['id'] ) ) {
	$params['id'] = 'default';
}


$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$allow = array( 'GET' );
	$attributes = $item->toArray();
	$id = ( $item->getId() !== null ? $item->getId() : $this->param( 'id', 'default' ) );
	$params = ['resource' => 'basket', 'id' => $id];

	if( ( $filter = $this->param( 'filter', [] ) ) !== [] ) {
		$params['filter'] = $filter;
	}

	if( $item->getId() === null ) {
		$allow = array( 'DELETE', 'GET', 'PATCH', 'POST' );
	}

	if( isset( $fields['basket'] ) ) {
		$attributes = array_intersect_key( $attributes, $fields['basket'] );
	}


	$relationships = [];
	$types = explode( ',', $this->param( 'included', 'basket/product,basket/service,basket/address,basket/coupon' ) );

	if( in_array( 'basket/product', $types ) )
	{
		foreach( $item->getProducts() as $position => $x ) {
			$relationships['basket/product']['data'][] = ['type' => 'basket/product', 'id' => $position];
		}
	}

	if( in_array( 'basket/service', $types ) )
	{
		foreach( $item->getServices() as $type => $x ) {
			$relationships['basket/service']['data'][] = ['type' => 'basket/service', 'id' => $type];
		}
	}

	if( in_array( 'basket/address', $types ) )
	{
		foreach( $item->getAddresses() as $type => $x ) {
			$relationships['basket/address']['data'][] = ['type' => 'basket/address', 'id' => $type];
		}
	}

	if( in_array( 'basket/coupon', $types ) )
	{
		foreach( $item->getCoupons() as $code => $x ) {
			$relationships['basket/coupon']['data'][] = ['type' => 'basket/coupon', 'id' => $code];
		}
	}


	return array(
		'id' => $id,
		'type' => 'basket',
		'links' => array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => $allow,
			),
		),
		'attributes' => $attributes,
		'relationships' => $relationships,
	);
};


$productFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$products = [];
	$id = ( $item->getId() !== null ? $item->getId() : $this->param( 'id', 'default' ) );

	foreach( $item->getProducts() as $position => $orderProduct )
	{
		$entry = ['id' => $position, 'type' => 'basket/product'];
		$entry['attributes'] = $orderProduct->toArray();

		if( $item->getId() === null && $orderProduct->getFlags() !== \Aimeos\MShop\Order\Item\Base\Product\Base::FLAG_IMMUTABLE )
		{
			$params = ['resource' => 'basket', 'id' => $id, 'related' => 'product', 'relatedid' => $position];
			$entry['links'] = array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
					'allow' => ['DELETE', 'PATCH'],
				),
			);
		}

		foreach( $orderProduct->getProducts() as $subProduct )
		{
			$subEntry = $subProduct->toArray();

			foreach( $subProduct->getAttributes() as $attribute ) {
				$subEntry['attribute'][] = $attribute->toArray();
			}

			$entry['attributes']['product'][] = $subEntry;
		}

		foreach( $orderProduct->getAttributes() as $attribute ) {
			$entry['attributes']['attribute'][] = $attribute->toArray();
		}

		$products[] = $entry;
	}

	return $products;
};


$serviceFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$services = [];
	$id = ( $item->getId() !== null ? $item->getId() : $this->param( 'id', 'default' ) );

	foreach( $item->getServices() as $type => $service )
	{
		$entry = ['id' => $type, 'type' => 'basket/service'];
		$entry['attributes'] = $service->toArray();

		if( $item->getId() === null )
		{
			$params = ['resource' => 'basket', 'id' => $id, 'related' => 'service', 'relatedid' => $type];
			$entry['links'] = array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
					'allow' => ['DELETE'],
				),
			);
		}

		foreach( $service->getAttributes() as $attribute ) {
			$entry['attributes']['attribute'][] = $attribute->toArray();
		}

		$services[] = $entry;
	}

	return $services;
};


$addressFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$addresses = [];
	$id = ( $item->getId() !== null ? $item->getId() : $this->param( 'id', 'default' ) );

	foreach( $item->getAddresses() as $type => $address )
	{
		$entry = ['id' => $type, 'type' => 'basket/address'];
		$entry['attributes'] = $address->toArray();

		if( $item->getId() === null )
		{
			$params = ['resource' => 'basket', 'id' => $id, 'related' => 'address', 'relatedid' => $type];
			$entry['links'] = array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
					'allow' => ['DELETE'],
				),
			);
		}

		$addresses[] = $entry;
	}

	return $addresses;
};


$couponFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$coupons = [];
	$id = ( $item->getId() !== null ? $item->getId() : $this->param( 'id', 'default' ) );

	foreach( $item->getCoupons() as $code => $list )
	{
		$entry = ['id' => $code, 'type' => 'basket/coupon'];

		if( $item->getId() === null )
		{
			$params = ['resource' => 'basket', 'id' => $id, 'related' => 'coupon', 'relatedid' => $code];
			$entry['links'] = array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
					'allow' => ['DELETE'],
				),
			);
		}

		$coupons[] = $entry;
	}

	return $coupons;
};


?>
{
	"meta": {
		"total": <?php echo ( isset( $this->item ) ? 1 : 0 ); ?>

	},
	"links": {
		"self": {
			"href": "<?php echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			"allow": <?php echo ( isset( $this->item ) && $this->item->getId() === null ? '["DELETE","GET","PATCH","POST"]' : '["GET"]' ); ?>

		}

		<?php if( isset( $this->item ) && $this->item->getId() === null ) : ?>
			,
			"basket/product": {
				"href": "<?php echo $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $params['id'], 'related' => 'product'], [], $config ); ?>",
				"allow": ["POST"]

			},
			"basket/service": {
				"href": "<?php echo $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $params['id'], 'related' => 'service'], [], $config ); ?>",
				"allow": ["POST"]

			},
			"basket/address": {
				"href": "<?php echo $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $params['id'], 'related' => 'address'], [], $config ); ?>",
				"allow": ["POST"]

			},
			"basket/coupon": {
				"href": "<?php echo $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $params['id'], 'related' => 'coupon'], [], $config ); ?>",
				"allow": ["POST"]

			}
		<?php endif; ?>

	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->item ) ) : ?>
		<?php
			$included = [];
			$types = explode( ',', $this->param( 'included', 'basket/product,basket/service,basket/address,basket/coupon' ) );

			if( in_array( 'basket/product', $types ) ) {
				$included = array_merge( $included, $productFcn( $this->item ) );
			}

			if( in_array( 'basket/service', $types ) ) {
				$included = array_merge( $included, $serviceFcn( $this->item ) );
			}

			if( in_array( 'basket/address', $types ) ) {
				$included = array_merge( $included, $addressFcn( $this->item ) );
			}

			if( in_array( 'basket/coupon', $types ) ) {
				$included = array_merge( $included, $couponFcn( $this->item ) );
			}
		?>

		"data": <?php echo json_encode( $entryFcn( $this->item ), JSON_PRETTY_PRINT ); ?>,

		"included": <?php echo json_encode( $included, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
