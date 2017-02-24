<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'get' );
$config = $this->config( 'client/jsonapi/url/config', array() );


$view = $this;
$enc = $this->encoder();

$ref = array( 'id', 'resource', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 100 ), 1 );


$map = $this->get( 'itemMap', array() );
$fields = $this->param( 'fields', array() );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Catalog\Item\Iface $item ) use ( $fields, $view, $target, $cntl, $action, $config )
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
				'href' => $view->url( $target, $cntl, $action, $params, array(), $config ),
				'allow' => array( 'GET' ),
			),
		),
		'attributes' => $attributes,
	);

	foreach( $item->getChildren() as $catItem ) {
		$entry['relationships']['catalog']['data'][] = array( 'id' => $catItem->getId(), 'type' => 'catalog' );
	}

	if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
	{
		foreach( $item->getListItems() as $listItem )
		{
			$domain = $listItem->getDomain();

			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$entry['attributes'][$domain][] = $refItem->toArray() + $listItem->toArray();
			}

		}
	}

	return $entry;
};


?>
{
	"meta": {
		"total": <?php echo ( isset( $this->items ) ? 1 : 0 ); ?>

	},

	"links": {
		"self": "<?php echo $this->url( $target, $cntl, $action, $params, array(), $config ); ?>"
	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>

		<?php
			$included = array();

			foreach( $this->items->getChildren() as $catItem ) {
				$included[] = $entryFcn( $catItem );
			}
		 ?>

		"data": <?php echo json_encode( $entryFcn( $this->items ) ); ?>,

		"included": <?php echo json_encode( $included ); ?>

	<?php endif; ?>

}
