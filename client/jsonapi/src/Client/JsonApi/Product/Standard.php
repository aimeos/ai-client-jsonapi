<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
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
	public function get( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$view = $this->view();

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
			$view->errors = $this->getErrorDetails( $e, 'mshop' );
		}
		catch( \Exception $e )
		{
			$status = $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500;
			$view->errors = $this->getErrorDetails( $e );
		}

		if( $view->param( 'aggregate' ) != '' )
		{
			/** client/jsonapi/product/template-aggregate
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
			$tplconf = 'client/jsonapi/product/template-aggregate';
			$default = 'aggregate-standard';
		}
		else
		{
			/** client/jsonapi/product/template
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
			$tplconf = 'client/jsonapi/product/template';
			$default = 'product/standard';
		}

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS' )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}


	/**
	 * Returns the available REST verbs and the available parameters
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function options( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$view = $this->view();

		$view->filter = [
			'f_search' => [
				'label' => 'Return products whose text matches the user input',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'f_catid' => [
				'label' => 'Return products associated to this category ID',
				'type' => 'string|array', 'default' => '', 'required' => false,
			],
			'f_listtype' => [
				'label' => 'Return products which are associated to categories with this list type',
				'type' => 'string', 'default' => 'default', 'required' => false,
			],
			'f_attrid' => [
				'label' => 'Return products that reference all attribute IDs',
				'type' => 'array', 'default' => '[]', 'required' => false,
			],
			'f_optid' => [
				'label' => 'Return products that reference at least one of the attribute IDs',
				'type' => 'array', 'default' => '[]', 'required' => false,
			],
			'f_oneid' => [
				'label' => 'Return products that reference at least one of the attribute IDs per attribute type',
				'type' => 'array[<typecode>]', 'default' => '[]', 'required' => false,
			],
			'f_supid' => [
				'label' => 'Return products that reference at least one of the supplier IDs',
				'type' => 'array', 'default' => '[]', 'required' => false,
			],
		];

		$view->sort = [
			'relevance' => [
				'label' => 'Sort products by their category position',
				'type' => 'string', 'default' => true, 'required' => false,
			],
			'name' => [
				'label' => 'Sort products by their name (ascending, "-name" for descending)',
				'type' => 'string', 'default' => false, 'required' => false,
			],
			'price' => [
				'label' => 'Sort products by their price (ascending, "-price" for descending)',
				'type' => 'string', 'default' => false, 'required' => false,
			],
			'ctime' => [
				'label' => 'Sort products by their creating date/time (ascending, "-ctime" for descending)',
				'type' => 'string', 'default' => false, 'required' => false,
			],
		];

		$tplconf = 'client/jsonapi/template-options';
		$default = 'options-standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS' )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( 200 );
	}


	/**
	 * Counts the number of products for the requested key
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function aggregate( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$view->data = $this->getController( $view )->sort()
			->slice( $view->param( 'page/offset', 0 ), $view->param( 'page/limit', 10000 ) )
			->aggregate( $view->param( 'aggregate' ) );

		return $response;
	}


	/**
	 * Returns the initialized product controller
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @return \Aimeos\Controller\Frontend\Product\Iface Initialized product controller
	 */
	protected function getController( \Aimeos\MW\View\Iface $view )
	{
		$context = $this->getContext();
		$cntl = \Aimeos\Controller\Frontend::create( $context, 'product' )->sort( $view->param( 'sort', 'relevance' ) );

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
		$level = $context->getConfig()->get( 'client/jsonapi/product/levels', \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );

		foreach( (array) $view->param( 'filter/f_oneid', [] ) as $list ) {
			$cntl->oneOf( $list );
		}

		$cntl->allOf( $view->param( 'filter/f_attrid', [] ) )
			->oneOf( $view->param( 'filter/f_optid', [] ) )
			->text( $view->param( 'filter/f_search' ) )
			->price( $view->param( 'filter/f_price' ) )
			->supplier( $view->param( 'filter/f_supid', [] ), $view->param( 'filter/f_listtype', 'default' ) )
			->category( $view->param( 'filter/f_catid' ), $view->param( 'filter/f_listtype', 'default' ), $level );

		$params = (array) $view->param( 'filter', [] );

		unset( $params['f_catid'], $params['f_listtype'] );
		unset( $params['f_supid'], $params['f_search'], $params['f_price'] );
		unset( $params['f_attrid'], $params['f_optid'], $params['f_oneid'] );

		return $cntl->parse( $params )->slice( $view->param( 'page/offset', 0 ), $view->param( 'page/limit', 48 ) );
	}


	/**
	 * Retrieves the item and adds the data to the view
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function getItem( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'product' );

		$view->items = $cntl->uses( $ref )->get( $view->param( 'id' ) );
		$view->total = 1;

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
	protected function getItems( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$total = 0;
		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		$view->items = $this->getController( $view )->uses( $ref )->search( $total );
		$view->total = $total;

		return $response;
	}
}
