<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


$enc = $this->encoder();

$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'get' );
$config = $this->config( 'client/jsonapi/url/config', [] );


$basketId = ( isset( $this->item ) && $this->item->getId() ? $this->item->getId() : ( $this->param( 'id' ) ?: 'default' ) );
$ref = array( 'resource', 'id', 'filter', 'page', 'sort', 'include', 'fields' ); // no related/relatedid for basket self URL
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

$pretty = $this->param( 'pretty' ) ? JSON_PRETTY_PRINT : 0;
$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item, $basketId ) use ( $fields, $target, $cntl, $action, $config )
{
	$allow = array( 'GET' );
	$attributes = $item->toArray();
	$params = ['resource' => 'basket', 'id' => $basketId];

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
	$types = explode( ',', $this->param( 'include', 'basket/product,basket/service,basket/address,basket/coupon' ) );

	if( in_array( 'basket/product', $types ) )
	{
		foreach( $item->getProducts() as $position => $list ) {
			$relationships['basket/product']['data'][] = ['type' => 'basket/product', 'id' => $position];
		}
	}

	if( in_array( 'basket/service', $types ) )
	{
		foreach( $item->getServices() as $type => $list )
		{
			if( count( $list ) > 0 ) {
				$relationships['basket/service']['data'][] = ['type' => 'basket/service', 'id' => $type];
			}
		}
	}

	if( in_array( 'basket/address', $types ) )
	{
		foreach( $item->getAddresses() as $type => $list ) {
			$relationships['basket/address']['data'][] = ['type' => 'basket/address', 'id' => $type];
		}
	}

	if( in_array( 'basket/coupon', $types ) )
	{
		foreach( $item->getCoupons() as $code => $list ) {
			$relationships['basket/coupon']['data'][] = ['type' => 'basket/coupon', 'id' => $code];
		}
	}

	if( $customer = $item->getCustomerItem() ) {
		$relationships['customer']['data'][] = ['type' => 'customer', 'id' => $customer->getId()];
	}


	return array(
		'id' => $basketId,
		'type' => 'basket',
		'links' => array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => $allow,
			),
		),
		'attributes' => $attributes,
		'relationships' => (object) $relationships,
	);
};


$productFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item, $basketId ) use ( $fields, $target, $cntl, $action, $config )
{
	$result = [];

	foreach( $item->getProducts() as $position => $orderProduct )
	{
		$entry = ['id' => $position, 'type' => 'basket/product'];
		$entry['attributes'] = $orderProduct->toArray();

		if( isset( $fields['basket/product'] ) ) {
			$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['basket/product'] );
		}

		if( $item->getId() === null && $orderProduct->getFlags() !== \Aimeos\MShop\Order\Item\Base\Product\Base::FLAG_IMMUTABLE )
		{
			$params = ['resource' => 'basket', 'id' => $basketId, 'related' => 'product', 'relatedid' => $position];
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

			foreach( $subProduct->getAttributeItems() as $attribute ) {
				$subEntry['attribute'][] = $attribute->toArray();
			}

			$entry['attributes']['product'][] = $subEntry;
		}

		foreach( $orderProduct->getAttributeItems() as $attribute ) {
			$entry['attributes']['attribute'][] = $attribute->toArray();
		}

		if( $product = $orderProduct->getProductItem() )
		{
			$entry['relationships']['product']['data'][] = ['type' => 'product', 'id' => $product->getId()];
			$result = array_merge( $result, $this->jincluded( $product, $fields ) );
		}

		$result['order/base/product'][] = $entry;
	}

	return $result;
};


$serviceFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item, $basketId ) use ( $fields, $target, $cntl, $action, $config )
{
	$result = [];

	foreach( $item->getServices() as $type => $list )
	{
		foreach( $list as $orderService )
		{
			$entry = ['id' => $type, 'type' => 'basket/service'];
			$entry['attributes'] = $orderService->toArray();

			if( isset( $fields['basket/service'] ) ) {
				$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['basket/service'] );
			}

			if( $item->getId() === null )
			{
				$params = ['resource' => 'basket', 'id' => $basketId, 'related' => 'service', 'relatedid' => $type];
				$entry['links'] = array(
					'self' => array(
						'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
						'allow' => ['DELETE'],
					),
				);
			}

			foreach( $orderService->getAttributeItems() as $attribute ) {
				$entry['attributes']['attribute'][] = $attribute->toArray();
			}

			if( $service = $orderService->getServiceItem() )
			{
				$entry['relationships']['service']['data'][] = ['type' => 'service', 'id' => $service->getId()];
				$result = array_merge( $result, $this->jincluded( $service, $fields ) );
			}

			$result['order/base/service'][] = $entry;
		}
	}

	return $result;
};


$addressFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item, $basketId ) use ( $fields, $target, $cntl, $action, $config )
{
	$list = [];

	foreach( $item->getAddresses() as $type => $addresses )
	{
		foreach( $addresses as $address )
		{
			$entry = ['id' => $type, 'type' => 'basket/address'];
			$entry['attributes'] = $address->toArray();

			if( isset( $fields['basket/address'] ) ) {
				$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['basket/address'] );
			}

			if( $item->getId() === null )
			{
				$params = ['resource' => 'basket', 'id' => $basketId, 'related' => 'address', 'relatedid' => $type];
				$entry['links'] = array(
					'self' => array(
						'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
						'allow' => ['DELETE'],
					),
				);
			}

			$list['order/base/address'][] = $entry;
		}
	}

	return $list;
};


$couponFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item, $basketId ) use ( $fields, $target, $cntl, $action, $config )
{
	$coupons = [];

	foreach( $item->getCoupons() as $code => $list )
	{
		$entry = ['id' => $code, 'type' => 'basket/coupon'];

		if( $item->getId() === null )
		{
			$params = ['resource' => 'basket', 'id' => $basketId, 'related' => 'coupon', 'relatedid' => $code];
			$entry['links'] = array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
					'allow' => ['DELETE'],
				),
			);
		}

		$coupons['order/base/coupon'][] = $entry;
	}

	return $coupons;
};


$customerFcn = function( \Aimeos\MShop\Order\Item\Base\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$result = [];
	$customer = $this->item->getCustomerItem();

	if( $customer && $customer->isAvailable() )
	{
		$params = ['resource' => 'customer', 'id' => $customer->getId()];
		$entry = ['id' => $customer->getId(), 'type' => 'customer'];
		$entry['attributes'] = $customer->toArray();

		if( isset( $fields['customer'] ) ) {
			$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['customer'] );
		}

		$entry['links'] = array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => ['GET'],
			),
		);

		$result['customer'][$customer->getId()] = $entry;
		$result = array_replace_recursive( $result, $this->jincluded( $customer, $fields ) );
	}

	return $result;
};


?>
{
	"meta": {
		"total": <?= ( isset( $this->item ) ? 1 : 0 ); ?>,
		"prefix": <?= json_encode( $this->get( 'prefix' ) ); ?>,
		"content-baseurl": "<?= $this->config( 'resource/fs/baseurl' ); ?>"
		<?php if( $this->csrf()->name() != '' ) : ?>
			, "csrf": {
				"name": "<?= $this->csrf()->name(); ?>",
				"value": "<?= $this->csrf()->value(); ?>"
			}
		<?php endif; ?>

	},
	"links": {
		"self": {
			"href": "<?= $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			"allow": <?= isset( $this->item ) && $this->item->getId() ? '["GET"]' : '["DELETE","GET","PATCH","POST"]' ?>

		}
		<?php if( isset( $this->item ) ) : ?>
			<?php if( $this->item->getId() === null ) : ?>
				,
				"basket/product": {
					"href": "<?= $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $basketId, 'related' => 'product'], [], $config ); ?>",
					"allow": ["DELETE", "POST"]
				},
				"basket/service": {
					"href": "<?= $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $basketId, 'related' => 'service'], [], $config ); ?>",
					"allow": ["DELETE", "POST"]
				},
				"basket/address": {
					"href": "<?= $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $basketId, 'related' => 'address'], [], $config ); ?>",
					"allow": ["DELETE", "POST"]
				},
				"basket/coupon": {
					"href": "<?= $this->url( $target, $cntl, $action, ['resource' => 'basket', 'id' => $basketId, 'related' => 'coupon'], [], $config ); ?>",
					"allow": ["DELETE", "POST"]
				}
			<?php else : ?>
				,
				"order": {
					"href": "<?= $this->url( $target, $cntl, $action, ['resource' => 'order'], [], $config ); ?>",
					"allow": ["POST"]
				}
			<?php endif; ?>
		<?php endif; ?>

	}
	<?php if( isset( $this->errors ) ) : ?>
		,"errors": <?= json_encode( $this->errors, $pretty ); ?>

	<?php elseif( isset( $this->item ) ) : ?>
		<?php
			$included = [];
			$types = explode( ',', $this->param( 'include', 'basket/product,basket/service,basket/address,basket/coupon' ) );

			if( in_array( 'basket/product', $types ) ) {
				$included = array_replace_recursive( $included, $productFcn( $this->item, $basketId ) );
			}

			if( in_array( 'basket/service', $types ) ) {
				$included = array_replace_recursive( $included, $serviceFcn( $this->item, $basketId ) );
			}

			if( in_array( 'basket/address', $types ) ) {
				$included = array_replace_recursive( $included, $addressFcn( $this->item, $basketId ) );
			}

			if( in_array( 'basket/coupon', $types ) ) {
				$included = array_replace_recursive( $included, $couponFcn( $this->item, $basketId ) );
			}

			if( in_array( 'customer', $types ) ) {
				$included = array_replace_recursive( $included, $customerFcn( $this->item ) );
			}
		?>

		,"data": <?= json_encode( $entryFcn( $this->item, $basketId ), $pretty ); ?>

		,"included": <?= map( $included )->flat( 1 )->toJson( $pretty ) ?>

	<?php endif; ?>

}
