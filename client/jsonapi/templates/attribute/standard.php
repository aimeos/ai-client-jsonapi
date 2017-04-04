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


$total = $this->get( 'total', 0 );
$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 100 ), 1 );

$first = ( $offset > 0 ? 0 : null );
$prev = ( $offset - $limit >= 0 ? $offset - $limit : null );
$next = ( $offset + $limit < $total ? $offset + $limit : null );
$last = ( ((int) ($total / $limit)) * $limit > $offset ? ((int) ($total / $limit)) * $limit : null );


$fields = $this->param( 'fields', array() );

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
				'href' => $this->url( $target, $cntl, $action, $params, array(), $config ),
				'allow' => array( 'GET' ),
			),
		),
		'attributes' => $attributes,
	);

	foreach( $item->getListItems() as $listItem )
	{
		if( ( $refItem = $listItem->getRefItem() ) !== null )
		{
			$domain = $listItem->getDomain();
			$entry['relationships'][$domain]['data'][] = array( 'id' => $refItem->getId(), 'type' => $domain );
		}
	}

	return $entry;
};


$refFcn = function( \Aimeos\MShop\Attribute\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
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
		"total": <?php echo $total; ?>

	},

	"links": {
		<?php if( is_array( $this->get( 'items' ) ) ) : ?>
			<?php if( $first !== null ) : ?>
				"first": "<?php $params['page']['offset'] = $first; echo $this->url( $target, $cntl, $action, $params, array(), $config ); ?>",
			<?php endif; ?>
			<?php if( $prev !== null ) : ?>
				"prev": "<?php $params['page']['offset'] = $prev; echo $this->url( $target, $cntl, $action, $params, array(), $config ); ?>",
			<?php endif; ?>
			<?php if( $next !== null ) : ?>
				"next": "<?php $params['page']['offset'] = $next; echo $this->url( $target, $cntl, $action, $params, array(), $config ); ?>",
			<?php endif; ?>
			<?php if( $last !== null ) : ?>
				"last": "<?php $params['page']['offset'] = $last; echo $this->url( $target, $cntl, $action, $params, array(), $config ); ?>",
			<?php endif; ?>
		<?php endif; ?>
		"self": "<?php $params['page']['offset'] = $offset; echo $this->url( $target, $cntl, $action, $params, array(), $config ); ?>"
	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>

		<?php
			$data = $included = array();
			$items = $this->get( 'items', array() );

			if( is_array( $items ) )
			{
				foreach( $items as $attrItem )
				{
					$data[] = $entryFcn( $attrItem );
					$included = array_merge( $included, $refFcn( $attrItem ) );
				}
			}
			else
			{
				$data = $entryFcn( $items );
				$included = $refFcn( $items );
			}
		 ?>

		"data": <?php echo json_encode( $data, JSON_PRETTY_PRINT ); ?>,

		"included": <?php echo json_encode( $included, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
