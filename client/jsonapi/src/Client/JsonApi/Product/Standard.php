<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Product;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API standard client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard
	extends \Aimeos\Client\JsonApi\Base
	implements \Aimeos\Client\JsonApi\Iface
{
	/**
	 * Returns the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function get( ServerRequestInterface $request, ResponseInterface $response )
	{
		$view = $this->getView();

		try
		{
			if( $view->param( 'aggregate' ) != '' ) {
				$response = $this->aggregate( $view, $request, $response );
			} elseif( $view->param( 'id' ) != '' ) {
				$response = $this->getItem( $view, $request, $response );
			} else {
				$response = $this->getItems( $view, $request, $response );
			}

			$status = 200;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$status = 404;
			$view->errors = array( array(
				'title' => $this->getContext()->getI18n()->dt( 'mshop', $e->getMessage() ),
				'detail' => $e->getTraceAsString(),
			) );
		}
		catch( \Exception $e )
		{
			$status = 500;
			$view->errors = array( array(
				'title' => $e->getMessage(),
				'detail' => $e->getTraceAsString(),
			) );
		}

		if( $view->param( 'aggregate' ) != '' )
		{
			/** client/jsonapi/product/standard/template-aggregate
			 * Relative path to the product aggregate JSON API template
			 *
			 * The template file contains the code and processing instructions
			 * to generate the result shown in the JSON API body. The
			 * configuration string is the path to the template file relative
			 * to the templates directory (usually in client/jsonapi/templates).
			 *
			 * You can overwrite the template file configuration in extensions and
			 * provide alternative templates. These alternative templates should be
			 * named like the default one but with the string "standard" replaced by
			 * an unique name. You may use the name of your project for this. If
			 * you've implemented an alternative client class as well, "standard"
			 * should be replaced by the name of the new class.
			 *
			 * @param string Relative path to the template creating the list of aggregated product counts
			 * @since 2017.03
			 * @category Developer
			 */
			$tplconf = 'client/jsonapi/product/standard/template-aggregate';
			$default = 'aggregate-standard.php';
		}
		else
		{
			/** client/jsonapi/product/standard/template
			 * Relative path to the product JSON API template
			 *
			 * The template file contains the code and processing instructions
			 * to generate the result shown in the JSON API body. The
			 * configuration string is the path to the template file relative
			 * to the templates directory (usually in client/jsonapi/templates).
			 *
			 * You can overwrite the template file configuration in extensions and
			 * provide alternative templates. These alternative templates should be
			 * named like the default one but with the string "standard" replaced by
			 * an unique name. You may use the name of your project for this. If
			 * you've implemented an alternative client class as well, "standard"
			 * should be replaced by the name of the new class.
			 *
			 * @param string Relative path to the template creating the body for the GET method of the JSON API
			 * @since 2017.03
			 * @category Developer
			 */
			$tplconf = 'client/jsonapi/product/standard/template';
			$default = 'product/standard.php';
		}

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}


	/**
	 * Counts the number of products for the requested key
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function aggregate( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response )
	{
		$key = $view->param( 'aggregate' );
		$size = $view->param( 'page/limit', 10000 );
		$start = $view->param( 'page/offset', 0 );

		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'product' );
		$filter = $this->getFilter( $view, null, '+', $start, $size );

		$view->data = $cntl->aggregate( $filter, $key );

		return $response;
	}


	/**
	 * Returns the items for the given domain with the items from the related domains included
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface[] $productItems Associative list of product IDs as keys and product items as values
	 * @param string[] $ref List of domain names that should be fetched together with the items
	 * @param string $domain Domain name of the items that should be returned
	 * @return \Aimeos\MShop\Attribute\Item\Iface[] Associative list of attribute IDs as keys and attribute items as values
	 */
	protected function getDomainItems( array $productItems, array $ref, $domain )
	{
		$ids = [];
		$context = $this->getContext();

		foreach( $productItems as $item ) {
			$ids = array_merge( $ids, array_keys( $item->getRefItems( $domain ) ) );
		}

		return \Aimeos\Controller\Frontend\Factory::createController( $context, $domain )->getItems( $ids, $ref );
	}


	/**
	 * Returns the initialized search filter
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param string|null $sort Sort code (e.g. relevance) or null for no sorting
	 * @param string $direction Sort direction, e.g. "+" for ascending, "-" for descending
	 * @param integer $start Slice start
	 * @param integer $size Slize size
	 * @return \Aimeos\MW\Criteria\Iface Initialize search filter
	 */
	protected function getFilter( \Aimeos\MW\View\Iface $view, $sort, $direction, $start, $size )
	{
		$listtype = $view->param( 'filter/f_listtype', 'default' );
		$attrIds = (array) $view->param( 'filter/f_attrid', [] );
		$optIds = (array) $view->param( 'filter/f_optid', [] );
		$oneIds = (array) $view->param( 'filter/f_oneid', [] );

		$context = $this->getContext();
		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $context, 'product' );

		$filter = $cntl->createFilter( $sort, $direction, $start, $size, $listtype );
		$filter = $cntl->addFilterAttribute( $filter, $attrIds, $optIds, $oneIds );

		if( ( $catid = $view->param( 'filter/f_catid' ) ) !== null )
		{
			$default = \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE;

			/** client/jsonapi/product/levels
			 * Include products of sub-categories in the product list of the current category
			 *
			 * Sometimes it may be useful to show products of sub-categories in the
			 * current category product list, e.g. if the current category contains
			 * no products at all or if there are only a few products in all categories.
			 *
			 * Possible constant values for this setting are:
			 * * 1 : Only products from the current category
			 * * 2 : Products from the current category and the direct child categories
			 * * 3 : Products from the current category and the whole category sub-tree
			 *
			 * Caution: Please keep in mind that displaying products of sub-categories
			 * can slow down your shop, especially if it contains more than a few
			 * products! You have no real control over the positions of the products
			 * in the result list too because all products from different categories
			 * with the same position value are placed randomly.
			 *
			 * Usually, a better way is to associate products to all categories they
			 * should be listed in. This can be done manually if there are only a few
			 * ones or during the product import automatically.
			 *
			 * @param integer Tree level constant
			 * @since 2017.03
			 * @category Developer
			 */
			$level = $context->getConfig()->get( 'client/jsonapi/product/levels', $default );

			$filter = $cntl->addFilterCategory( $filter, $catid, $level, $sort, $direction, $listtype );
		}

		if( ( $search = $view->param( 'filter/f_search' ) ) !== null ) {
			$filter = $cntl->addFilterText( $filter, $search, $sort, $direction, $listtype );
		}

		$params = $view->param( 'filter', [] );
		unset( $params['f_attrid'], $params['f_optid'], $params['f_oneid'] );
		unset( $params['f_listtype'], $params['f_catid'], $params['f_search'] );
		$filter = $this->initCriteriaConditions( $filter, ['filter' => $params] );

		return $filter;
	}


	/**
	 * Retrieves the item and adds the data to the view
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function getItem( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response )
	{
		$map = [];
		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'product' );

		$view->items = $cntl->getItem( $view->param( 'id' ), $ref );
		$view->total = 1;

		if( in_array( 'product', $ref, true ) ) {
			$map['product'] = $this->getDomainItems( array( $view->items ), $ref, 'product' );
		}

		if( in_array( 'attribute', $ref, true ) )
		{
			$productItems = ( isset( $map['product'] ) ? array_merge( array( $view->items ), $map['product'] ) : [] );
			$map['attribute'] = $this->getDomainItems( $productItems, $ref, 'attribute' );
		}

		$view->itemMap = $map;

		return $response;
	}


	/**
	 * Retrieves the items and adds the data to the view
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function getItems( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response )
	{
		$total = 0;
		$map = [];
		$direction  = '+';

		$ref = $view->param( 'include', [] );
		$size = $view->param( 'page/limit', 48 );
		$start = $view->param( 'page/offset', 0 );
		$sort = $view->param( 'sort', 'relevance' );

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		if( $sort[0] === '-' )
		{
			$sort = substr( $sort, 1 );
			$direction = '-';
		}

		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'product' );
		$filter = $this->getFilter( $view, $sort, $direction, $start, $size );

		$view->items = $cntl->searchItems( $filter, $ref, $total );
		$view->total = $total;

		if( in_array( 'product', $ref, true ) ) {
			$map['product'] = $this->getDomainItems( $view->items, $ref, 'product' );
		}

		if( in_array( 'attribute', $ref, true ) )
		{
			$productItems = ( isset( $map['product'] ) ? array_merge( $view->items, $map['product'] ) : [] );
			$map['attribute'] = $this->getDomainItems( $productItems, $ref, 'attribute' );
		}

		$view->itemMap = $map;

		return $response;
	}
}
