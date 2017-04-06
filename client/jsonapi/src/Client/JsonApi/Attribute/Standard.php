<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
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
	public function get( ServerRequestInterface $request, ResponseInterface $response )
	{
		$view = $this->getView();

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

		/** client/jsonapi/attribute/standard/template
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
		$tplconf = 'client/jsonapi/attribute/standard/template';
		$default = 'attribute/standard.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
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
		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'attribute' );

		$view->items = $cntl->getItem( $view->param( 'id' ), $ref );
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
	protected function getItems( \Aimeos\MW\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response )
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

		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'attribute' );

		$filter = $cntl->createFilter();
		$filter = $this->initCriteriaConditions( $filter, $view->param() );
		$filter = $cntl->addFilterTypes( $filter, $attrTypes );

		$items = $cntl->searchItems( $filter, $ref, $total );

		foreach( $items as $id => $item ) {
			$attrMap[$item->getType()][$id] = $item;
		}

		if( !empty( $attrTypes ) )
		{
			$sorted = [];

			foreach( $attrTypes as $type )
			{
				if( isset( $attrMap[$type] ) ) {
					$sorted = array_merge( $sorted, $attrMap[$type] );
				}
			}

			$items = $sorted;
		}

		$view->items = $items;
		$view->total = $total;

		return $response;
	}
}
