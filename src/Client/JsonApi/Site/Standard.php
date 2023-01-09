<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2023
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Site;

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
	/** client/jsonapi/site/name
	 * Class name of the used site client implementation
	 *
	 * Each default JSON API client can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the client factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Client\JsonApi\Site\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Client\JsonApi\Site\Mysite
	 *
	 * then you have to set the this configuration option:
	 *
	 *  client/jsonapi/site/name = Mysite
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MySite"!
	 *
	 * @param string Last part of the class name
	 * @since 2021.04
	 * @category Developer
	 */

	/** client/jsonapi/site/decorators/excludes
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
	 * @since 2021.04
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/site/decorators/global
	 * @see client/jsonapi/site/decorators/local
	 */

	/** client/jsonapi/site/decorators/global
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
	 *  client/jsonapi/site/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
	 * "site" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2021.04
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/site/decorators/excludes
	 * @see client/jsonapi/site/decorators/local
	 */

	/** client/jsonapi/site/decorators/local
	 * Adds a list of local decorators only to the JsonApi client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Client\JsonApi\Site\Decorator\*") around the JsonApi
	 * client.
	 *
	 *  client/jsonapi/site/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Client\JsonApi\Site\Decorator\Decorator2" only to the
	 * "site" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2021.04
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/site/decorators/excludes
	 * @see client/jsonapi/site/decorators/global
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

		/** client/jsonapi/site/template
		 * Relative path to the site lists JSON API template
		 *
		 * The template file contains the code and processing instructions
		 * to generate the result shown in the JSON API body. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in templates/client/jsonapi).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "default" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "standard"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating the body for the GET method of the JSON API
		 * @since 2021.04
		 * @site Developer
		 */
		$tplconf = 'client/jsonapi/site/template';
		$default = 'site/standard';

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
	 * @param \Aimeos\Base\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function getItem( \Aimeos\Base\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$map = [];
		$ref = $view->param( 'include', [] );
		$level = \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE;

		if( is_string( $ref ) ) {
			$ref = explode( ',', str_replace( '.', '/', $ref ) );
		}

		if( in_array( 'locale/site', $ref, true ) )
		{
			/** client/jsonapi/site/deep
			 * Load the site tree instead of the nodes of the first level only
			 *
			 * If you want to use the site filter component to display the whole
			 * site tree without loading data in an asynchcron way, set this
			 * configuration option to "1" or true.
			 *
			 * **Warning:** If your site tree has a lot of nodes, it will
			 * take a very long time to render all categories. Thus, it's only
			 * recommended for small site trees with a limited node size
			 * (less than 50).
			 *
			 * @param bool True for site tree, false for first level only
			 * @since 2021.04
			 */
			$deep = $view->config( 'client/jsonapi/site/deep', false );

			$level = $deep ? \Aimeos\MW\Tree\Manager\Base::LEVEL_TREE : \Aimeos\MW\Tree\Manager\Base::LEVEL_LIST;
		}

		$total = 1;
		$cntl = \Aimeos\Controller\Frontend::create( $this->context(), 'site' )
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
