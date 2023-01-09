<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
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
	/** client/jsonapi/attribute/name
	 * Class name of the used attribute client implementation
	 *
	 * Each default JSON API client can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the client factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Client\JsonApi\Attribute\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Client\JsonApi\Attribute\Myattribute
	 *
	 * then you have to set the this configuration option:
	 *
	 *  client/jsonapi/attribute/name = Myattribute
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyAttribute"!
	 *
	 * @param string Last part of the class name
	 * @since 2017.03
	 * @category Developer
	 */

	/** client/jsonapi/attribute/decorators/excludes
	 * Excludes decorators added by the "common" option from the JSON API clients
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "client/jsonapi/common/decorators/default" before they are wrapped
	 * around the JsonApi client.
	 *
	 *  client/jsonapi/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Client\JsonApi\Common\Decorator\*") added via
	 * "client/jsonapi/common/decorators/default" for the JSON API client.
	 *
	 * @param array List of decorator names
	 * @since 2017.07
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/attribute/decorators/global
	 * @see client/jsonapi/attribute/decorators/local
	 */

	/** client/jsonapi/attribute/decorators/global
	 * Adds a list of globally available decorators only to the JsonApi client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Client\JsonApi\Common\Decorator\*") around the JsonApi
	 * client.
	 *
	 *  client/jsonapi/attribute/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
	 * "attribute" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2017.07
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/attribute/decorators/excludes
	 * @see client/jsonapi/attribute/decorators/local
	 */

	/** client/jsonapi/attribute/decorators/local
	 * Adds a list of local decorators only to the JsonApi client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Client\JsonApi\Attribute\Decorator\*") around the JsonApi
	 * client.
	 *
	 *  client/jsonapi/attribute/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Client\JsonApi\Attribute\Decorator\Decorator2" only to the
	 * "attribute" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2017.07
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/attribute/decorators/excludes
	 * @see client/jsonapi/attribute/decorators/global
	 */


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
		 * to the templates directory (usually in templates/client/jsonapi).
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
	 * @param \Aimeos\Base\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function getItem( \Aimeos\Base\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', str_replace( '.', '/', $ref ) );
		}

		$cntl = \Aimeos\Controller\Frontend::create( $this->context(), 'attribute' );

		$view->items = $cntl->uses( $ref )->get( $view->param( 'id' ) );
		$view->total = 1;

		return $response;
	}


	/**
	 * Retrieves the items and adds the data to the view
	 *
	 * @param \Aimeos\Base\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function getItems( \Aimeos\Base\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
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
		$attrTypes = $this->context()->config()->get( 'client/jsonapi/attribute/types', [] );

		$total = 0;
		$attrMap = [];

		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', str_replace( '.', '/', $ref ) );
		}

		$items = \Aimeos\Controller\Frontend::create( $this->context(), 'attribute' )
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
