<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
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
$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Service\Item\Iface $item, array $prices, array $feConfig ) use ( $fields, $target, $cntl, $action, $config )
{
	$metadata = [];
	$id = $item->getId();
	$type = $item->getResourceType();

	$attributes = $item->toArray();
	unset( $attributes['service.config'] ); // don't expose private information

	$params = array( 'resource' => $type, 'id' => $id );
	$basketParams = [ 'resource' => 'basket', 'id' => 'default', 'related' => 'service', 'relatedid' => $item->getType() ];

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
	}

	if( isset( $prices[$id] ) ) {
		$attributes['price'] = $prices[$id]->toArray();
	}

	if( isset( $feConfig[$id] ) )
	{
		foreach( $feConfig[$id] as $code => $attr ) {
			$metadata[$code] = $attr->toArray();
		}
	}

	$entry = array(
		'id' => $id,
		'type' => $type,
		'links' => array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => ['GET'],
			),
			'basket/service' => array(
				'href' => $this->url( $target, $cntl, $action, $basketParams, [], $config ),
				'allow' => ['POST'],
				'meta' => $metadata,
			),
		),
		'attributes' => $attributes,
	);

	foreach( $item->getListItems() as $listItem )
	{
		if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
		{
			$type = $refItem->getResourceType();
			$entry['relationships'][$type]['data'][] = [
				'id' => $refItem->getId(),
				'type' => $type,
				'attributes' => $listItem->toArray(),
			];
		}
	}

	return $entry;
};


$refFcn = function( \Aimeos\MShop\Common\Item\Iface $item, array $map ) use ( $fields, $target, $cntl, $action, $config, &$refFcn )
{
	$id = $item->getId();
	$type = $item->getResourceType();

	if( isset( $map[$type][$id] ) ) {
		return $map;
	}

	$attributes = $item->toArray();

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
	}

	$entry = ['id' => $id, 'type' => $type, 'attributes' => $attributes];
	$map[$type][$id] = $entry; // first content, avoid infinite loops

	if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
	{
		foreach( $item->getListItems() as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
			{
				$reftype = $refItem->getResourceType();
				$data = ['id' => $refItem->getId(), 'type' => $reftype, 'attributes' => $listItem->toArray()];
				$entry['relationships'][$reftype]['data'][] = $data;
				$map = $refFcn( $refItem, $map );
			}
		}
	}

	if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
	{
		foreach( $item->getPropertyItems() as $propItem )
		{
			$propId = $propItem->getId();
			$propType = $propItem->getResourceType();
			$entry['relationships'][$propType]['data'][] = ['id' => $propId, 'type' => $propType];
			$map = $refFcn( $propItem, $map );
		}
	}

	$map[$type][$id] = $entry; // full content

	return $map;
};


$inclFcn = function( \Aimeos\MShop\Common\Item\Iface $item ) use ( $refFcn )
{
	$map = [];

	if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
	{
		foreach( $item->getListItems() as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() ) {
				$map = $refFcn( $refItem, $map );
			}
		}
	}

	if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
	{
		foreach( $item->getPropertyItems() as $propertyItem ) {
			$map = $refFcn( $propertyItem, $map );
		}
	}

	return $map;
};


$flatFcn = function( array $map )
{
	$result = [];

	foreach( $map as $list )
	{
		foreach( $list as $entry ) {
			$result[] = $entry;
		}
	}

	return $result;
};


?>
{
	"meta": {
		"total": <?= $this->get( 'total', 0 ); ?>,
		"prefix": <?= json_encode( $this->get( 'prefix' ) ); ?>,
		"content-baseurl": "<?= $this->config( 'client/html/common/content/baseurl' ); ?>"

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

		,"errors": <?= json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>
		<?php
			$prices = $this->get( 'prices', [] );
			$feConfig = $this->get( 'attributes', [] );

			if( is_array( $this->items ) )
			{
				$data = $included = [];
				foreach( (array) $this->items as $item )
				{
					$data[] = $entryFcn( $item, $prices, $feConfig );
					$included = array_merge( $included, $flatFcn( $inclFcn( $item ) ) );
				}
			}
			else
			{
				$data = $entryFcn( $this->items, $prices, $feConfig );
				$included = $flatFcn( $inclFcn( $this->items ) );
			}
		?>

		,"data": <?= json_encode( $data, JSON_PRETTY_PRINT ); ?>

		,"included": <?= json_encode( $included, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
