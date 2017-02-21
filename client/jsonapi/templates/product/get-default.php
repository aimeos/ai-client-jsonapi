<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


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
$config = $this->config( 'client/jsonapi/url/config', array() );


/** client/jsonapi/partials/template-errors
 * Relative path to the error partial template file
 *
 * Partials are templates which are reused in other templates and generate
 * reoccuring blocks filled with data from the assigned values. The error
 * partial creates the "error" part for the JSON API response.
 *
 * The partial template files are usually stored in the templates/partials/ folder
 * of the core or the extensions. The configured path to the partial file must
 * be relative to the templates/ folder, e.g. "partials/error-default.php".
 *
 * @param string Relative path to the template file
 * @since 2017.03
 * @category Developer
 */

/** client/jsonapi/partials/template-included
 * Relative path to the "included" partial template file
 *
 * Partials are templates which are reused in other templates and generate
 * reoccuring blocks filled with data from the assigned values. The "included"
 * partial creates the "included" part for the JSON API response.
 *
 * The partial template files are usually stored in the templates/partials/ folder
 * of the core or the extensions. The configured path to the partial file must
 * be relative to the templates/ folder, e.g. "partials/included-default.php".
 *
 * @param string Relative path to the template file
 * @since 2017.03
 * @category Developer
 */


$view = $this;
$enc = $this->encoder();


$ref = array( 'id', 'resource', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );


$total = $this->get( 'total', 0 );
$offset = max( $this->param( 'page/offset', 0 ), 0 );
$limit = max( $this->param( 'page/limit', 48 ), 1 );

$first = ( $offset > 0 ? 0 : null );
$prev = ( $offset - $limit >= 0 ? $offset - $limit : null );
$next = ( $offset + $limit < $total ? $offset + $limit : null );
$last = ( ((int) ($total / $limit)) * $limit > $offset ? ((int) ($total / $limit)) * $limit : null );


$map = $this->get( 'itemMap', array() );
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

	if( $item instanceof \Aimeos\MShop\Product\Item\Iface )
	{
		foreach( $item->getPropertyItems() as $propertyItem ) {
			$entry['attributes']['product/property'][] = $propertyItem->toArray();
		}
	}

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


$refFcn = function( \Aimeos\MShop\Common\Item\Iface $item, $level ) use ( $entryFcn, &$refFcn, &$map )
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
			$id = $refItem->getId();

			if( isset( $map[$domain][$id] ) ) {
				$refItem = $map[$domain][$id];
			}

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
				foreach( $items as $prodId => $productItem )
				{
					$data[] = $entryFcn( $productItem );
					$included = array_merge( $included, $refFcn( $productItem, 0 ) );
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
