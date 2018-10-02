<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
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


$removeFcn = function( $key, $array )
{
	unset( $array[$key] );
	return $array;
};


$entryFcn = function( \Aimeos\MShop\Common\Item\Lists\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$relid = $item->getId();
	$id = $item->getParentId();
	$attributes = $item->toArray();
	$type = 'customer/lists';
	$params = array( 'resource' => 'customer', 'id' => $id, 'related' => 'relationships', 'relatedid' => $relid );

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
	}

	$entry = array(
		'id' => $relid,
		'type' => $type,
		'links' => array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => array( 'DELETE', 'GET', 'PATCH' ),
			),
		),
		'attributes' => $attributes,
	);

	return $entry;
};


?>
{
	"meta": {
		"total": <?= $this->get( 'total', 0 ); ?>,
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
		"self": "<?= $this->url( $target, $cntl, $action, $params, [], $config ); ?>",
		"related": "<?= $this->url( $target, $cntl, $action, $removeFcn( 'relatedid', $params ), [], $config ); ?>"
	}

	<?php if( isset( $this->errors ) ) : ?>

		,"errors": <?= json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>
		<?php
			$data = [];
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

		,"data": <?= json_encode( $data, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
