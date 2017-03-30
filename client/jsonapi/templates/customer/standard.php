<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'index' );
$config = $this->config( 'client/jsonapi/url/config', array() );


$enc = $this->encoder();


$ref = array( 'id', 'resource', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

if( !isset( $params['id'] ) ) {
	$params['id'] = '';
}


$fields = $this->param( 'fields', array() );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Customer\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$attributes = $item->toArray();
	$type = $item->getResourceType();
	$params = array( 'resource' => $type, 'id' => $item->getId() );

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
	}

	$entry = array(
		'id' => $item->getId(),
		'type' => $item->getResourceType(),
		'links' => array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, array(), $config ),
				'allow' => array( 'DELETE', 'GET', 'PATCH', 'POST' ),
			),
		),
		'attributes' => $attributes,
	);

	foreach( $item->getAddressItems() as $addrItem ) {
		$entry['attributes']['customer/address'][] = $addrItem->toArray();
	}

	foreach( $item->getListItems() as $listItem )
	{
		$domain = $listItem->getDomain();
		$basic = array( 'id' => $refItem->getId(), 'type' => $domain );
		$entry['relationships'][$domain]['data'][] = $basic + $listItem->toArray();
	}

	return $entry;
};


$refFcn = function( \Aimeos\MShop\Customer\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$list = array();

	foreach( $item->getListItems() as $listItem )
	{
		$domain = $listItem->getDomain();

		$attributes = $refItem->toArray();
		$params = array( 'resource' => $item->getResourceType(), 'id' => $refItem->getId() );

		if( isset( $fields[$type] ) ) {
			$attributes = array_intersect_key( $attributes, $fields[$type] );
		}

		$list[] = array(
			'id' => $refItem->getId(),
			'type' => $refItem->getResourceType(),
			'links' => array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, array(), $config ),
					'allow' => array( 'DELETE', 'GET', 'PATCH', 'POST' ),
				),
			),
			'attributes' => $attributes,
		);
	}

	return $list;
};


?>
{
	"meta": {
		"total": <?php echo ( isset( $this->item ) ? 1 : 0 ); ?>

	}

	<?php if( isset( $this->item ) ) : ?>

		,"links": {
			"self": {
				"href": "<?php echo $this->url( $target, $cntl, $action, ['resource' => 'customer', 'id' => $params['id']], [], $config ); ?>",
				"allow": ["DELETE","GET","PATCH","POST"]

			},
			"related": {
				"customer/address": {
					"href": "<?php echo $this->url( $target, $cntl, $action, ['resource' => 'customer', 'id' => $params['id'], 'related' => 'address'], [], $config ); ?>",
					"allow": ["DELETE","GET","PATCH","POST"]

				}
			}
		}

	<?php endif; ?>
	<?php if( isset( $this->errors ) ) : ?>

		,"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->item ) ) : ?>

		,"data": <?php echo json_encode( $entryFcn( $this->item ) ); ?>

		,"included": <?php echo json_encode( $refFcn( $this->item ) ); ?>

	<?php endif; ?>

}
