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

	foreach( $item->getChildren() as $catItem ) {
		$entry['relationships']['catalog']['data'][] = array( 'id' => $catItem->getId(), 'type' => 'catalog' );
	}

	foreach( $item->getListItems() as $listItem )
	{
		if( ( $refItem = $listItem->getRefItem() ) !== null )
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


$refFcn = function( \Aimeos\MShop\Catalog\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$list = [];

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
		"total": <?php echo ( isset( $this->item ) ? 1 : 0 ); ?>

	},

	"links": {
		"self": "<?php echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>"
	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->item ) ) : ?>
		<?php
			$included = $refFcn( $this->item );

			foreach( $this->item->getChildren() as $catItem )
			{
				$included[] = $entryFcn( $catItem );
				$included = array_merge( $included, $refFcn( $catItem ) );
			}
		 ?>

		"data": <?php echo json_encode( $entryFcn( $this->item ), JSON_PRETTY_PRINT ); ?>,

		"included": <?php echo json_encode( $included, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
