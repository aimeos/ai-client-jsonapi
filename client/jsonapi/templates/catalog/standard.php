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


$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 100 ), 1 );


$ref = array( 'resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );
$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Catalog\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	if( $item->isAvailable() === false ) {
		return [];
	}

	$id = $item->getId();
	$type = $item->getResourceType();
	$params = array( 'resource' => $type, 'id' => $item->getId() );
	$attributes = $item->toArray();

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
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
	);

	foreach( $item->getChildren() as $catItem )
	{
		if( $catItem->isAvailable() ) {
			$entry['relationships']['catalog']['data'][] = array( 'id' => $catItem->getId(), 'type' => 'catalog' );
		}
	}

	foreach( $item->getListItems() as $listItem )
	{
		if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
		{
			$domain = $listItem->getDomain();
			$entry['relationships'][$domain]['data'][] = [
				'id' => $refItem->getId(),
				'type' => $refItem->getResourceType(),
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

	if( $item instanceof \Aimeos\MShop\Catalog\Item\Iface )
	{
		$params = array( 'resource' => $type, 'id' => $id );
		$entry['links'] = array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => array( 'GET' ),
			),
		);

		foreach( $item->getChildren() as $childItem )
		{
			if( $childItem->isAvailable() )
			{
				$cattype = $childItem->getResourceType();
				$entry['relationships'][$cattype]['data'][] = ['id' => $childItem->getId(), 'type' => $cattype];
				$map = $refFcn( $childItem, $map );
			}
		}
	}

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

	if( $item instanceof \Aimeos\MShop\Catalog\Item\Iface )
	{
		foreach( $item->getChildren() as $childItem )
		{
			if( $childItem->isAvailable() ) {
				$map = $refFcn( $childItem, $map );
			}
		}
	}

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
		"total": <?= ( isset( $this->item ) ? 1 : 0 ); ?>,
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
	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?= json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->item ) ) : ?>

		"data": <?= json_encode( $entryFcn( $this->item ), JSON_PRETTY_PRINT ); ?>,

		"included": <?= json_encode( $flatFcn( $inclFcn( $this->item ) ), JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
