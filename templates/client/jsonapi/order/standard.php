<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
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

	foreach( $item->getProducts() as $product ) {
		$relationships['order/product']['data'][] = ['type' => 'order/product', 'id' => $product->getId()];
	}

	foreach( $item->getServices() as $list )
	{
		foreach( $list as $service ) {
			$relationships['order/service']['data'][] = ['type' => 'order/service', 'id' => $service->getId()];
		}
	}

	foreach( $item->getAddresses() as $list )
	{
		foreach( $list as $address ) {
			$relationships['order/address']['data'][] = ['type' => 'order/address', 'id' => $address->getId()];
		}
	}

	foreach( $item->getCoupons() as $code => $x ) {
		$relationships['order/coupon']['data'][] = ['type' => 'order/coupon', 'id' => $code];
	}

	if( $customer = $item->getCustomerItem() ) {
		$relationships['customer']['data'][] = ['type' => 'customer', 'id' => $customer->getId()];
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

	foreach( $item->getProducts() as $orderProduct )
	{
		$entry = ['id' => $orderProduct->getId(), 'type' => 'order/product'];
		$entry['attributes'] = $orderProduct->toArray();

		if( isset( $fields['order/product'] ) ) {
			$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['order/product'] );
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

		$result['order/product'][] = $entry;
	}

	return $result;
};


$serviceFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) use ( $fields )
{
	$result = [];

	foreach( $item->getServices() as $type => $list )
	{
		foreach( $list as $orderService )
		{
			$entry = ['id' => $orderService->getId(), 'type' => 'order/service'];
			$entry['attributes'] = $orderService->toArray();

			if( isset( $fields['order/service'] ) ) {
				$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['order/service'] );
			}

			foreach( $orderService->getAttributeItems() as $attribute ) {
				$entry['attributes']['attribute'][] = $attribute->toArray();
			}

			if( $service = $orderService->getServiceItem() )
			{
				$entry['relationships']['service']['data'][] = ['type' => 'service', 'id' => $service->getId()];
				$result = array_merge( $result, $this->jincluded( $service, $fields ) );
			}

			$result['order/service'][] = $entry;
		}
	}

	return $result;
};


$addressFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) use ( $fields )
{
	$list = [];

	foreach( $item->getAddresses() as $type => $addresses )
	{
		foreach( $addresses as $address )
		{
			$entry = ['id' => $address->getId(), 'type' => 'order/address'];
			$entry['attributes'] = $address->toArray();

			if( isset( $fields['order/address'] ) ) {
				$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['order/address'] );
			}

			$list['order/address'][] = $entry;
		}
	}

	return $list;
};


$couponFcn = function( \Aimeos\MShop\Order\Item\Iface $item )
{
	$coupons = [];

	foreach( $item->getCoupons() as $code => $list ) {
		$coupons['order/coupon'][] = ['id' => $code, 'type' => 'order/coupon'];
	}

	return $coupons;
};


$customerFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$result = [];

	if( ( $customer = $item->getCustomerItem() ) !== null && $customer->isAvailable() )
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
