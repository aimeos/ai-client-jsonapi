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
		if( ( $refItem = $listItem->getRefItem() ) !== null )
		{
			$type = $refItem->getResourceType();
			$data = array( 'id' => $listItem->getRefId(), 'type' => $type, 'attributes' => $listItem->toArray() );
			$entry['relationships'][$type]['data'][] = $data;
		}
	}

	return $entry;
};


$refFcn = function( \Aimeos\MShop\Common\Item\ListRef\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$list = array();

	foreach( $item->getListItems() as $listItem )
	{
		if( ( $refItem = $listItem->getRefItem() ) !== null )
		{
			$attributes = $refItem->toArray();
			$type = $refItem->getResourceType();

			if( isset( $fields[$type] ) ) {
				$attributes = array_intersect_key( $attributes, $fields[$type] );
			}

			$list[] = array( 'id' => $refItem->getId(), 'type' => $type, 'attributes' => $attributes );
		}
	}

	return $list;
};


?>
{
	"meta": {
		"total": <?php echo $this->get( 'total', 0 ); ?>

	},
	"links": {
		"self": "<?php echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>"
	}

	<?php if( isset( $this->errors ) ) : ?>

		,"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

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
					$included = array_merge( $included, $refFcn( $item ) );
				}
			}
			else
			{
				$data = $entryFcn( $this->items, $prices, $feConfig );
				$included = $refFcn( $this->items );

			}
		?>

		,"data": <?php echo json_encode( $data, JSON_PRETTY_PRINT ); ?>

		,"included": <?php echo json_encode( $included, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
