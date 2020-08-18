<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2020
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


$entryFcn = function( \Aimeos\MShop\Customer\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
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
				'allow' => array( 'DELETE', 'GET', 'PATCH' ),
			),
		),
		'attributes' => ['customer.id' => $id]
	);

	if( $this->get( 'nodata', false ) == true ) { // don't expose more data to attackers
		return $entry;
	}


	$entry['attributes'] = $attributes;

	foreach( $item->getAddressItems() as $addrItem )
	{
		$type = $addrItem->getResourceType();
		$entry['relationships'][$type]['data'][] = array( 'id' => $addrItem->getId(), 'type' => $type );
	}

	foreach( $item->getListItems() as $listId => $listItem )
	{
		if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
		{
			$type = $refItem->getResourceType();
			$params = array( 'resource' => $type, 'id' => $id, 'related' => 'relationships', 'relatedid' => $listId );

			$entry['relationships'][$type]['data'][] = [
				'id' => $refItem->getId(),
				'type' => $type,
				'attributes' => $listItem->toArray(),
				'links' => [
					'self' => [
						'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
						'allow' => ['DELETE', 'PATCH'],
					],
				],
			];
		}
	}

	foreach( $item->getPropertyItems() as $propItem )
	{
		$propType = $propItem->getResourceType();
		$entry['relationships'][$propType]['data'][] = ['id' => $propItem->getId(), 'type' => $propType];
	}

	return $entry;
};


$custAddrFcn = function( \Aimeos\MShop\Customer\Item\Address\Iface $item, array $entry ) use ( $target, $cntl, $action, $config )
{
	$id = $item->getId();
	$type = $item->getResourceType();

	$params = ['resource' => 'customer', 'id' => $item->getParentId(), 'related' => $type, 'relatedid' => $id];
	$basketParams = ['resource' => 'basket', 'id' => 'default', 'related' => 'address', 'relatedid' => 'payment'];

	$entry['links'] = [
		'self' => [
			'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
			'allow' => ['DELETE', 'GET', 'PATCH'],
		],
		'basket/address' => [
			'href' => $this->url( $target, $cntl, $action, $basketParams, [], $config ),
			'allow' => ['POST'],
		],
	];

	return $entry;
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
		"self": "<?= $this->url( $target, $cntl, $action, $params, [], $config ); ?>"
		<?php if( isset( $this->item ) ) : ?>
			,"customer/address": {
				"href": "<?= $this->url( $target, $cntl, $action, ['resource' => 'customer', 'id' => $this->item->getId(), 'related' => 'address'], [], $config ); ?>",
				"allow": ["GET","POST"]
			}
		<?php endif; ?>
	}
	<?php if( isset( $this->errors ) ) : ?>
		,"errors": <?= json_encode( $this->errors, $pretty ); ?>

	<?php elseif( isset( $this->item ) ) : ?>
		,"data": <?= json_encode( $entryFcn( $this->item ), $pretty ); ?>

		,"included": <?= json_encode( $this->jincluded( $this->item, $fields, ['customer/address' => $custAddrFcn] ), $pretty ); ?>

	<?php endif; ?>

}
