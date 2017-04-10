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


$entryFcn = function( \Aimeos\MShop\Locale\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$id = $item->getId();
	$attributes = $item->toArray();
	$type = $item->getResourceType();
	$optTarget = $this->config( 'client/jsonapi/url/options/target' );

	$params = array( 'resource' => $type, 'id' => $id );
	$resourceParams = ['locale' => $item->getLanguageId(), 'currency' => $item->getCurrencyId()];

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
			'resources' => array(
				'href' => $this->url( $optTarget, $cntl, $action, $resourceParams, [], $config ),
				'allow' => array( 'OPTIONS' ),
			),
		),
		'attributes' => $attributes,
	);

	return $entry;
};


?>
{
	"meta": {
		"total": <?php echo $this->get( 'total', 0 ); ?>

	},

	"links": {
		"self": "<?php echo $this->url( $target, $cntl, $action, $params, [], $config ); ?>"
	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>

		<?php
			$data = [];
			$items = $this->get( 'items', [] );

			if( is_array( $items ) )
			{
				foreach( $items as $localeItem ) {
					$data[] = $entryFcn( $localeItem );
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
