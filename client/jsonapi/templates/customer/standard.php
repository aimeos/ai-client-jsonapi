<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
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
		if( ( $refItem = $listItem->getRefItem() ) !== null )
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

	return $entry;
};


$refFcn = function( \Aimeos\MShop\Customer\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$list = [];

	foreach( $item->getAddressItems() as $addrItem )
	{
		$id = $addrItem->getId();
		$attributes = $addrItem->toArray();
		$type = $addrItem->getResourceType();

		$params = array( 'resource' => 'customer', 'id' => $item->getId(), 'related' => $type, 'relatedid' => $id );
		$basketParams = array( 'resource' => 'basket', 'id' => 'default', 'related' => 'address', 'relatedid' => 'payment' );

		if( isset( $fields[$type] ) ) {
			$attributes = array_intersect_key( $attributes, $fields[$type] );
		}

		$list[] = array(
			'id' => $id,
			'type' => $type,
			'links' => array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
					'allow' => array( 'DELETE', 'GET', 'PATCH' ),
				),
				'basket/address' => array(
					'href' => $this->url( $target, $cntl, $action, $basketParams, [], $config ),
					'allow' => ['POST'],
				),
			),
			'attributes' => $attributes,
		);
	}

	foreach( $item->getRefItems() as $refItem )
	{
		$id = $refItem->getId();
		$type = $refItem->getResourceType();
		$attributes = $refItem->toArray();

		if( isset( $fields[$type] ) ) {
			$attributes = array_intersect_key( $attributes, $fields[$type] );
		}

		$list[] = array(
			'id' => $id,
			'type' => $type,
			'attributes' => $attributes,
		);
	}

	return $list;
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

		<?php if( isset( $this->item ) ) : ?>
			,"customer/address": {
				"href": "<?= $this->url( $target, $cntl, $action, ['resource' => 'customer', 'id' => $this->item->getId(), 'related' => 'address'], [], $config ); ?>",
				"allow": ["GET","POST"]
			}
		<?php endif; ?>
	}

	<?php if( isset( $this->errors ) ) : ?>

		,"errors": <?= json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->item ) ) : ?>

		,"data": <?= json_encode( $entryFcn( $this->item ), JSON_PRETTY_PRINT ); ?>

		,"included": <?= json_encode( $refFcn( $this->item ), JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
