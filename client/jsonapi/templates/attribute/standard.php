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


$total = $this->get( 'total', 0 );
$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 100 ), 1 );

$first = ( $offset > 0 ? 0 : null );
$prev = ( $offset - $limit >= 0 ? $offset - $limit : null );
$next = ( $offset + $limit < $total ? $offset + $limit : null );
$last = ( ((int) ($total / $limit)) * $limit > $offset ? ((int) ($total / $limit)) * $limit : null );


$ref = array( 'resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );
$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Attribute\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$id = $item->getId();
	$type = $item->getResourceType();
	$params = array( 'resource' => $type, 'id' => $id );
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

	foreach( $item->getPropertyItems() as $propItem )
	{
		$entry['relationships']['attribute/property']['data'][] = [
			'id' => $propItem->getId(),
			'type' => 'attribute/property',
		];
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


$refFcn = function( \Aimeos\MShop\Common\Item\Iface $item, array $map ) use ( $fields, &$refFcn )
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
		"total": <?= $total; ?>,
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
		<?php if( is_array( $this->get( 'items' ) ) ) : ?>
			<?php if( $first !== null ) : ?>
				"first": "<?php $params['page']['offset'] = $first; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
			<?php if( $prev !== null ) : ?>
				"prev": "<?php $params['page']['offset'] = $prev; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
			<?php if( $next !== null ) : ?>
				"next": "<?php $params['page']['offset'] = $next; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
			<?php if( $last !== null ) : ?>
				"last": "<?php $params['page']['offset'] = $last; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
		<?php endif; ?>
		"self": "<?php $params['page']['offset'] = $offset; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>"
	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?= json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>
		<?php
			$items = $this->get( 'items', [] );

			if( is_array( $items ) )
			{
				$data = $included = [];
				foreach( $items as $attrItem )
				{
					$data[] = $entryFcn( $attrItem );
					$included = array_merge( $included, $flatFcn( $inclFcn( $attrItem ) ) );
				}
			}
			else
			{
				$data = $entryFcn( $items );
				$included = $flatFcn( $inclFcn( $items ) );
			}
		 ?>

		"data": <?= json_encode( $data, JSON_PRETTY_PRINT ); ?>,

		"included": <?= json_encode( $included, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
