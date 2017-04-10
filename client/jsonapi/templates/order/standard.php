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


$entryFcn = function( \Aimeos\MShop\Order\Item\Iface $item, \Aimeos\MShop\Common\Item\Helper\Form\Iface $form = null ) use ( $fields, $target, $cntl, $action, $config )
{
	$id = $item->getId();
	$attributes = $item->toArray();
	$type = $item->getResourceType();
	$params = array( 'resource' => $type, 'id' => $id );

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

	if( $form !== null )
	{
		$entry['links']['process']['href'] = $form->getUrl();
		$entry['links']['process']['allow'] = [( $form->getMethod() !== 'REDIRECT' ? $form->getMethod() : 'GET' )];
		$entry['links']['process']['meta'] = [];

		foreach( $form->getValues() as $key => $attr ) {
			$entry['links']['process']['meta'][$key] = $attr->toArray();
		}
	}

	return $entry;
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

		,"data": <?php echo json_encode( $data, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}
