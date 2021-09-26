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


$ref = array( 'resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

$pretty = $this->param( 'pretty' ) ? JSON_PRETTY_PRINT : 0;
$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Order\Item\Iface $item, \Aimeos\MShop\Common\Helper\Form\Iface $form = null ) use ( $fields, $target, $cntl, $action, $config )
{
	$relationships = [];
	$id = $item->getId();
	$attributes = $item->toArray();
	$type = $item->getResourceType();
	$params = array( 'resource' => $type, 'id' => $id );

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
	}

	if( $baseItem = $item->getBaseItem() )
	{
		$baseattr = $baseItem->toArray();
		$basetype = $baseItem->getResourceType();

		if( isset( $fields[$basetype] ) ) {
			$baseattr = array_intersect_key( $baseattr, $fields[$basetype] );
		}

		$attributes += $baseattr;
		$attributes['product'] = $attributes['service'] = [];
		$attributes['address'] = $attributes['coupon'] = [];

		foreach( $baseItem->getProducts() as $product )
		{
			$entry = $product->toArray();
			$relationships['order/base/product']['data'][] = ['type' => 'order/base/product', 'id' => $product->getId()];

			if( isset( $fields[$product->getResourceType()] ) ) {
				$entry = array_intersect_key( $entry, $fields[$product->getResourceType()] );
			}

			foreach( $product->getProducts() as $subproduct )
			{
				$relationships['order/base/product']['data'][] = ['type' => 'order/base/product', 'id' => $subproduct->getId()];

				if( isset( $fields[$product->getResourceType()] ) ) {
					$entry['product'][] = array_intersect_key( $subproduct->toArray(), $fields[$product->getResourceType()] );
				} else {
					$entry['product'][] = $subproduct->toArray();
				}
			}

			$attributes['product'][] = $entry;
		}

		foreach( $baseItem->getServices() as $list )
		{
			foreach( $list as $service )
			{
				$relationships['order/base/service']['data'][] = ['type' => 'order/base/service', 'id' => $service->getId()];

				if( isset( $fields[$service->getResourceType()] ) ) {
					$attributes['service'][] = array_intersect_key( $service->toArray(), $fields[$service->getResourceType()] );
				} else {
					$attributes['service'][] = $service->toArray();
				}
			}
		}

		foreach( $baseItem->getAddresses() as $list )
		{
			foreach( $list as $address )
			{
				$relationships['order/base/address']['data'][] = ['type' => 'order/base/address', 'id' => $address->getId()];

				if( isset( $fields[$address->getResourceType()] ) ) {
					$attributes['address'][] = array_intersect_key( $address->toArray(), $fields[$address->getResourceType()] );
				} else {
					$attributes['address'][] = $address->toArray();
				}
			}
		}

		foreach( $baseItem->getCoupons() as $code => $x )
		{
			$relationships['order/base/coupon']['data'][] = ['type' => 'order/base/coupon', 'id' => $code];
			$attributes['coupon'][] = $code;
		}

		if( $customer = $baseItem->getCustomerItem() ) {
			$relationships['customer']['data'][] = ['type' => 'customer', 'id' => $customer->getId()];
		}
	}

	$entry = array(
		'id' => $id,
		'type' => $type,
		'links' => array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => array( 'GET' ),
			),
		),
		'attributes' => $attributes,
		'relationships' => (object) $relationships,
	);

	if( $form !== null )
	{
		$entry['links']['process']['href'] = $form->getUrl();
		$entry['links']['process']['allow'] = [( $form->getMethod() !== 'REDIRECT' ? $form->getMethod() : 'GET' )];
		$entry['links']['process']['meta'] = [];

		foreach( $form->getValues() as $key => $attr ) {
			$entry['links']['process']['meta'][$key] = $attr->toArray();
		}
	}

	return $entry;
};


$productFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) use ( $fields )
{
	$result = [];
	$baseItem = $item->getBaseItem();

	if( $baseItem )
	{
		foreach( $baseItem->getProducts() as $orderProduct )
		{
			$entry = ['id' => $orderProduct->getId(), 'type' => 'order/base/product'];
			$entry['attributes'] = $orderProduct->toArray();

			if( isset( $fields['order/base/product'] ) ) {
				$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['order/base/product'] );
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
	}

	return $result;
};


$serviceFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) use ( $fields )
{
	$result = [];
	$baseItem = $item->getBaseItem();

	if( $baseItem )
	{
		foreach( $baseItem->getServices() as $type => $list )
		{
			foreach( $list as $orderService )
			{
				$entry = ['id' => $orderService->getId(), 'type' => 'order/base/service'];
				$entry['attributes'] = $orderService->toArray();

				if( isset( $fields['order/base/service'] ) ) {
					$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['order/base/service'] );
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
	}

	return $result;
};


$addressFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) use ( $fields )
{
	$list = [];
	$baseItem = $item->getBaseItem();

	if( $baseItem )
	{
		foreach( $baseItem->getAddresses() as $type => $addresses )
		{
			foreach( $addresses as $address )
			{
				$entry = ['id' => $address->getId(), 'type' => 'order/base/address'];
				$entry['attributes'] = $address->toArray();

				if( isset( $fields['order/base/address'] ) ) {
					$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['order/base/address'] );
				}

				$list['order/base/address'][] = $entry;
			}
		}
	}

	return $list;
};


$couponFcn = function( \Aimeos\MShop\Order\Item\Iface $item )
{
	$coupons = [];
	$baseItem = $item->getBaseItem();

	if( $baseItem )
	{
		foreach( $baseItem->getCoupons() as $code => $list ) {
			$coupons['order/base/coupon'][] = ['id' => $code, 'type' => 'order/base/coupon'];
		}
	}

	return $coupons;
};


$customerFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$result = [];
	$baseItem = $item->getBaseItem();

	if( $baseItem && ( $customer = $baseItem->getCustomerItem() ) !== null && $customer->isAvailable() )
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
		"total": <?= $this->get( 'total', 0 ); ?>,
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
		"self": "<?= $this->url( $target, $cntl, $action, $params, [], $config ); ?>"
	}
	<?php if( isset( $this->errors ) ) : ?>
		,"errors": <?= json_encode( $this->errors, $pretty ); ?>

	<?php elseif( isset( $this->items ) ) : ?>
		<?php
			$data = $included = [];
			$items = $this->get( 'items', map() );

			if( is_map( $items ) )
			{
				foreach( $items as $item )
				{
					$data[] = $entryFcn( $item, $this->get( 'form' ) );
					$included = array_replace_recursive( $included, $couponFcn( $item ) );
					$included = array_replace_recursive( $included, $addressFcn( $item ) );
					$included = array_replace_recursive( $included, $productFcn( $item ) );
					$included = array_replace_recursive( $included, $serviceFcn( $item ) );
					$included = array_replace_recursive( $included, $customerFcn( $item ) );
				}
			}
			else
			{
				$data = $entryFcn( $items, $this->get( 'form' ) );
				$included = array_replace_recursive( $included, $couponFcn( $items ) );
				$included = array_replace_recursive( $included, $addressFcn( $items ) );
				$included = array_replace_recursive( $included, $productFcn( $items ) );
				$included = array_replace_recursive( $included, $serviceFcn( $items ) );
				$included = array_replace_recursive( $included, $customerFcn( $items ) );
			}
		 ?>

		,"data": <?= json_encode( $data, $pretty ); ?>

		,"included": <?= map( $included )->flat( 1 )->toJson( $pretty ) ?>

	<?php endif; ?>

}
