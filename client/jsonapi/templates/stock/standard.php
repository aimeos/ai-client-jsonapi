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


$total = $this->get( 'total', 0 );
$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 100 ), 1 );

$first = ( $offset > 0 ? 0 : null );
$prev = ( $offset - $limit >= 0 ? $offset - $limit : null );
$next = ( $offset + $limit < $total ? $offset + $limit : null );
$last = ( ((int) ($total / $limit)) * $limit > $offset ? ((int) ($total / $limit)) * $limit : null );


$ref = array( 'resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );
$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Stock\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
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
				'allow' => array( 'GET' ),
			),
		),
		'attributes' => $attributes,
	);

	return $entry;
};


?>
{
	"meta": {
		"total": <?php echo $total; ?>

	},

	"links": {
		<?php if( is_array( $this->get( 'items' ) ) ) : ?>
			<?php if( $first !== null ) : ?>
				"first": "<?php $params['page']['offset'] = $first; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
			<?php if( $prev !== null ) : ?>
				"prev": "<?php $params['page']['offset'] = $prev; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
			<?php if( $next !== null ) : ?>
				"next": "<?php $params['page']['offset'] = $next; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
			<?php if( $last !== null ) : ?>
				"last": "<?php $params['page']['offset'] = $last; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
			<?php endif; ?>
		<?php endif; ?>
		"self": "<?php $params['page']['offset'] = $offset; echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>"
	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>

		<?php
			$data = $included = [];
			$items = $this->get( 'items', [] );

			if( is_array( $items ) )
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

		"data": <?php echo json_encode( $data, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
