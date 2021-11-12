<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Catalog;

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
			$response = $this->getItem( $view, $request, $response );
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

		/** client/jsonapi/catalog/template
		 * Relative path to the catalog lists JSON API template
		 *
		 * The template file contains the code and processing instructions
		 * to generate the result shown in the JSON API body. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in client/jsonapi/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "default" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "standard"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating the body for the GET method of the JSON API
		 * @since 2017.03
		 * @category Developer
		 */
		$tplconf = 'client/jsonapi/catalog/template';
		$default = 'catalog/standard';

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
		return $this->getOptionsResponse( $request, $response, 'GET,OPTIONS' );
	}


	/**
	 * Retrieves the items and adds the data to the view
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function getItem( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$map = [];
		$ref = $view->param( 'include', [] );
		$level = \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE;

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		if( in_array( 'catalog', $ref, true ) )
		{
			/** client/jsonapi/catalog/deep
			 * Load the category tree instead of the nodes of the first level only
			 *
			 * If you want to use the catalog filter component to display the whole
			 * category tree without loading data in an asynchcron way, set this
			 * configuration option to "1" or true.
			 *
			 * **Warning:** If your category tree has a lot of nodes, it will
			 * take a very long time to render all categories. Thus, it's only
			 * recommended for small category trees with a limited node size
			 * (less than 50).
			 *
			 * @param bool True for category tree, false for first level only
			 * @since 2020.10
			 * @see controller/frontend/catalog/levels-always
			 * @see controller/frontend/catalog/levels-only
			 * @see client/html/catalog/filter/tree/deep
			 */
			$deep = $view->config( 'client/jsonapi/catalog/deep', false );

			$level = $deep ? \Aimeos\MW\Tree\Manager\Base::LEVEL_TREE : \Aimeos\MW\Tree\Manager\Base::LEVEL_LIST;
		}

		$total = 1;
		$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'catalog' )->uses( $ref )
			->slice( $view->param( 'page/offset', 0 ), $view->param( 'page/limit', 100 ) );

		if( ( $cond = (array) $view->param( 'filter', [] ) ) === [] ) {
			$view->items = $cntl->root( $view->param( 'id' ) )->getTree( $level );
		} else {
			$view->items = $cntl->parse( $cond )->search( $total );
		}

		$view->total = $total;

		return $response;
	}
}
