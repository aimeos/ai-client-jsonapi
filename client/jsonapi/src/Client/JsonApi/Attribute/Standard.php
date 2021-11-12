<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Attribute;

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
			if( $view->param( 'id' ) != '' ) {
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

		/** client/jsonapi/attribute/template
		 * Relative path to the attribute lists JSON API template
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
		$tplconf = 'client/jsonapi/attribute/template';
		$default = 'attribute/standard';

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

		$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'attribute' );

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
		/** client/jsonapi/attribute/types
		 * List of attribute types that should be displayed in this order in the catalog filter
		 *
		 * The attribute section in the catalog filter component can display
		 * all attributes a visitor can use to reduce the listed products
		 * to those that contains one or more attributes. By default, all
		 * available attributes will be displayed and ordered by their
		 * attribute type.
		 *
		 * With this setting, you can limit the attribute types to only thoses
		 * whose names are part of the setting value. Furthermore, a particular
		 * order for the attribute types can be enforced that is different
		 * from the standard order.
		 *
		 * @param array List of attribute type codes
		 * @since 2017.03
		 * @category Developer
		 */
		$attrTypes = $this->getContext()->getConfig()->get( 'client/jsonapi/attribute/types', [] );

		$total = 0;
		$attrMap = [];

		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		$items = \Aimeos\Controller\Frontend::create( $this->getContext(), 'attribute' )
			->uses( $ref )->sort( $view->param( 'sort', 'position' ) )->type( $attrTypes )
			->slice( $view->param( 'page/offset', 0 ), $view->param( 'page/limit', 25 ) )
			->parse( (array) $view->param( 'filter', [] ) )
			->search( $total );

		foreach( $items as $id => $item ) {
			$attrMap[$item->getType()][$id] = $item;
		}

		if( !empty( $attrTypes ) )
		{
			$sorted = map();

			foreach( $attrTypes as $type )
			{
				if( isset( $attrMap[$type] ) ) {
					$sorted->merge( $attrMap[$type] );
				}
			}

			$items = $sorted;
		}

		$view->items = $items;
		$view->total = $total;

		return $response;
	}
}
