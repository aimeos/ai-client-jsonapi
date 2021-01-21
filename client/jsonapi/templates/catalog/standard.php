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

$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 100 ), 1 );

$ref = array( 'resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

$pretty = $this->param( 'pretty' ) ? JSON_PRETTY_PRINT : 0;
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
			$ltype = $listItem->getResourceType();
			$type = $refItem->getResourceType();
			$attributes = $listItem->toArray();

			if( isset( $fields[$ltype] ) ) {
				$attributes = array_intersect_key( $attributes, $fields[$ltype] );
			}

			$data = array( 'id' => $refItem->getId(), 'type' => $type, 'attributes' => $attributes );
			$entry['relationships'][$type]['data'][] = $data;
		}
	}

	return $entry;
};


$catFcn = function( \Aimeos\MShop\Catalog\Item\Iface $item, array $entry ) use ( $target, $cntl, $action, $config )
{
	$params = ['resource' => 'catalog', 'id' => $item->getId()];
	$entry['links'] = [
		'self' => [
			'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
			'allow' => ['GET']
		]
	];

	return $entry;
};


?>
{
	"meta": {
		"total": <?= $this->get( 'total', 0 )  ?>,
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
			$data = [];
			$items = $this->get( 'items', map() );
			$included = $this->jincluded( $items, $fields, ['catalog' => $catFcn] );

			if( is_map( $items ) )
			{
				foreach( $items as $item ) {
					$data[] = $entryFcn( $item );
				}
			}
			else
			{
				$data = $entryFcn( $items );
			}
		 ?>

		,"data": <?= json_encode( $data, $pretty ); ?>

		,"included": <?= map( $included )->flat( 1 )->toJson( $pretty ) ?>

	<?php endif; ?>

}
