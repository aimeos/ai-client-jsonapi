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


$entryFcn = function( \Aimeos\MShop\Service\Item\Iface $item, \Aimeos\Map $prices, array $feConfig ) use ( $fields, $target, $cntl, $action, $config )
{
	$metadata = [];
	$id = $item->getId();
	$type = $item->getResourceType();

	$attributes = $item->toArray();
	unset( $attributes['service.config'] ); // don't expose private information

	$params = array( 'resource' => $type, 'id' => $id );
	$basketParams = ['resource' => 'basket', 'id' => 'default', 'related' => 'service', 'relatedid' => $item->getType()];

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
	}

	if( ( $price = $prices->get( $id ) ) !== null ) {
		$attributes['price'] = $price->toArray();
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
			$data = [];
			$items = $this->get( 'items', map() );
			$prices = $this->get( 'prices', map() );
			$feConfig = $this->get( 'attributes', [] );
			$included = $this->jincluded( $this->items, $fields );

			if( is_map( $items ) )
			{
				foreach( $items as $item ) {
					$data[] = $entryFcn( $item, $prices, $feConfig );
				}
			}
			else
			{
				$data = $entryFcn( $items, $prices, $feConfig );
			}
		?>

		,"data": <?= json_encode( $data, $pretty ); ?>

		,"included": <?= map( $included )->flat( 1 )->toJson( $pretty ) ?>

	<?php endif; ?>

}
