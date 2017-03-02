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


$entryFcn = function( \Aimeos\MShop\Common\Item\Iface $item ) use ( $fields, $view, $target, $cntl, $action, $config )
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

	if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
	{
		foreach( $item->getListItems() as $listItem )
		{
			$domain = $listItem->getDomain();

			if( in_array( $domain, array( 'media', 'price', 'text' ), true )
				&& ( $refItem = $listItem->getRefItem() ) !== null
			) {
				$entry['attributes'][$domain][] = $refItem->toArray() + $listItem->toArray();
			}

			if( !in_array( $domain, array( 'media', 'price', 'text' ), true )
				&& ( $refItem = $listItem->getRefItem() ) !== null
			) {
				$basic = array( 'id' => $refItem->getId(), 'type' => $domain );
				$entry['relationships'][$domain]['data'][] = $basic + $listItem->toArray();
			}
		}
	}

	return $entry;
};


$refFcn = function( \Aimeos\MShop\Common\Item\Iface $item, $level ) use ( $entryFcn, &$refFcn )
{
	$list = array();

	if( !( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface ) || $level > 1 ) {
		return $list;
	}

	foreach( $item->getListItems() as $listItem )
	{
		$domain = $listItem->getDomain();

		if( !in_array( $domain, array( 'media', 'price', 'text' ), true )
			&& ( $refItem = $listItem->getRefItem() ) !== null
		) {
			$list[] = $entryFcn( $refItem );
			$list = array_merge( $list, $refFcn( $refItem, ++$level ) );
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
					$included = array_merge( $included, $refFcn( $attrItem, 0 ) );
				}
			}
			else
			{
				$data = $entryFcn( $items );
				$included = $refFcn( $items, 0 );
			}
		 ?>

		"data": <?php echo json_encode( $data ); ?>,

		"included": <?php echo json_encode( $included ); ?>

	<?php endif; ?>

}
