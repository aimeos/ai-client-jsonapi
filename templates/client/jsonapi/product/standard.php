<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2025
 * @package Client
 * @subpackage JsonApi
 */


$enc = $this->encoder();


/** client/jsonapi/url/target
 * Destination of the URL where the client specified in the URL is known
 *
 * The destination can be a page ID like in a content management system or the
 * module of a software development framework. This "target" must contain or know
 * the client that should be called by the generated URL.
 *
 * @param string Destination of the URL
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/controller
 * @see client/jsonapi/url/action
 * @see client/jsonapi/url/config
 */
$target = $this->config( 'client/jsonapi/url/target' );

/** client/jsonapi/url/controller
 * Name of the client whose action should be called
 *
 * In Model-View-Controller (MVC) applications, the client contains the methods
 * that create parts of the output displayed in the generated HTML page. Controller
 * names are usually alpha-numeric.
 *
 * @param string Name of the client
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/target
 * @see client/jsonapi/url/action
 * @see client/jsonapi/url/config
 */
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );

/** client/jsonapi/url/action
 * Name of the action that should create the output
 *
 * In Model-View-Controller (MVC) applications, actions are the methods of a
 * client that create parts of the output displayed in the generated HTML page.
 * Action names are usually alpha-numeric.
 *
 * @param string Name of the action
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/target
 * @see client/jsonapi/url/controller
 * @see client/jsonapi/url/config
 */
$action = $this->config( 'client/jsonapi/url/action', 'get' );

/** client/jsonapi/url/config
 * Associative list of configuration options used for generating the URL
 *
 * You can specify additional options as key/value pairs used when generating
 * the URLs, like
 *
 *  client/jsonapi/url/config = array( 'absoluteUri' => true )
 *
 * The available key/value pairs depend on the application that embeds the e-commerce
 * framework. This is because the infrastructure of the application is used for
 * generating the URLs. The full list of available config options is referenced
 * in the "see also" section of this page.
 *
 * @param string Associative list of configuration options
 * @since 2017.03
 * @category Developer
 * @see client/jsonapi/url/target
 * @see client/jsonapi/url/controller
 * @see client/jsonapi/url/action
 */
$config = $this->config( 'client/jsonapi/url/config', [] );


$total = $this->get( 'total', 0 );
$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 48 ), 1 );

$first = ( $offset > 0 ? 0 : null );
$prev = ( $offset - $limit >= 0 ? $offset - $limit : null );
$next = ( $offset + $limit < $total ? $offset + $limit : null );
$last = ( ( (int) ( $total / $limit ) ) * $limit > $offset ? ( (int) ( $total / $limit ) ) * $limit : null );


$ref = array( 'resource', 'id', 'related', 'relatedid', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

$pretty = $this->param( 'pretty' ) ? JSON_PRETTY_PRINT : 0;
$fields = $this->param( 'fields', [] );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Product\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$id = $item->getId();
	$attributes = $item->toArray();
	$type = $item->getResourceType();

	$params = array( 'resource' => $type, 'id' => $id );
	$basketParams = ['resource' => 'basket', 'id' => 'default', 'related' => 'product'];

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
			'basket.product' => array(
				'href' => $this->url( $target, $cntl, $action, $basketParams, [], $config ),
				'allow' => ['POST'],
			),
		),
		'attributes' => $attributes,
	);

	if( $typeItem = $item->getTypeItem() )
	{
		$entry['relationships'][$type . '.type']['data'][] = [
			'id' => $typeItem->getId(),
			'type' => $type . '.type',
		];
	}

	foreach( $item->getPropertyItems() as $propertyItem )
	{
		$rtype = str_replace( '/', '.', $propertyItem->getResourceType() );
		$entry['relationships'][$rtype]['data'][] = array( 'id' => $propertyItem->getId(), 'type' => $rtype );
	}

	foreach( $item->getListItems() as $listItem )
	{
		if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
		{
			$ltype = str_replace( '/', '.', $listItem->getResourceType() );
			$rtype = str_replace( '/', '.', $refItem->getResourceType() );
			$attributes = $listItem->toArray();

			if( isset( $fields[$ltype] ) ) {
				$attributes = array_intersect_key( $attributes, $fields[$ltype] );
			}

			$data = array( 'id' => $refItem->getId(), 'type' => $rtype, 'attributes' => $attributes );
			$entry['relationships'][$rtype]['data'][] = $data;
		}
	}

	foreach( $item->getStockItems() as $stockItem )
	{
		if( $stockItem->isAvailable() ) {
			$entry['relationships']['stock']['data'][] = array( 'id' => $stockItem->getId(), 'type' => 'stock' );
		}
	}

	if( $siteItem = $item->getSiteItem() ) {
		$entry['relationships']['locale.site']['data'][] = array( 'id' => $siteItem->getId(), 'type' => 'locale.site' );
	}

	return $entry;
};


$includeFcn = function( \Aimeos\MShop\Product\Item\Iface $item ) use ( $fields, $target, $cntl, $action, $config )
{
	$result = [];

	foreach( $item->getStockItems() as $id => $stockItem )
	{
		if( $stockItem->isAvailable() )
		{
			$params = ['resource' => 'stock', 'id' => $id];
			$entry = ['id' => $id, 'type' => 'stock'];
			$entry['attributes'] = $stockItem->toArray();

			if( isset( $fields['stock'] ) ) {
				$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['stock'] );
			}

			$entry['links'] = array(
				'self' => array(
					'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
					'allow' => ['GET'],
				),
			);

			$result['stock'][$id] = $entry;
		}
	}

	if( $siteItem = $item->getSiteItem() )
	{
		$params = ['resource' => 'locale.site', 'id' => $siteItem->getId()];
		$entry = ['id' => $siteItem->getId(), 'type' => 'locale.site'];
		$entry['attributes'] = $siteItem->toArray();

		if( isset( $fields['locale.site'] ) ) {
			$entry['attributes'] = array_intersect_key( $entry['attributes'], $fields['locale.site'] );
		}

		$entry['links'] = array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, [], $config ),
				'allow' => ['GET'],
			),
		);

		$result['locale.site'][$siteItem->getId()] = $entry;
	}

	return $result;
};


?>
{
	"meta": {
		"total": <?= $total; ?>,
		"prefix": <?= json_encode( $this->get( 'prefix' ) ); ?>,
		"content-baseurl": "<?= $this->config( 'resource/fs/baseurl' ); ?>",
		"content-baseurls": {
			"fs-media": "<?= $this->config( 'resource/fs-media/baseurl' ) ?>",
			"fs-mimeicon": "<?= $this->config( 'resource/fs-mimeicon/baseurl' ) ?>",
			"fs-theme": "<?= $this->config( 'resource/fs-theme/baseurl' ) ?>"
		}
		<?php if( $this->csrf()->name() != '' ) : ?>
			, "csrf": {
				"name": "<?= $this->csrf()->name(); ?>",
				"value": "<?= $this->csrf()->value(); ?>"
			}
		<?php endif; ?>
	},
	"links": {
		<?php if( is_map( $this->get( 'items' ) ) ) : ?>
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
	}
	<?php if( isset( $this->errors ) ) : ?>
		,"errors": <?= json_encode( $this->errors, $pretty ); ?>

	<?php elseif( isset( $this->items ) ) : ?>
		<?php
			$data = $included = [];
			$items = $this->get( 'items', map() );

			if( is_map( $items ) )
			{
				foreach( $items as $item )
				{
					$data[] = $entryFcn( $item );
					$included = array_replace_recursive( $included, $includeFcn( $item ) );
				}
			}
			else
			{
				$data = $entryFcn( $items );
				$included = array_replace_recursive( $included, $includeFcn( $items ) );
			}
		?>

		,"data": <?= json_encode( $data, $pretty ); ?>

		,"included": <?= map( $this->jincluded( $items, $fields ) )->replace( $included )->flat( 1 )->toJson( $pretty ); ?>

	<?php endif; ?>

}
