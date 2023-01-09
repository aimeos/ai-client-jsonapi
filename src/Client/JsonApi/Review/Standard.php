<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020-2023
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Review;

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
	/** client/jsonapi/review/name
	 * Class name of the used review client implementation
	 *
	 * Each default JSON API client can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the client factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Client\JsonApi\Review\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Client\JsonApi\Review\Myreview
	 *
	 * then you have to set the this configuration option:
	 *
	 *  client/jsonapi/review/name = Myreview
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyReview"!
	 *
	 * @param string Last part of the class name
	 * @since 2017.03
	 * @category Developer
	 */

	/** client/jsonapi/review/decorators/excludes
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
	 * @since 2020.10
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/review/decorators/global
	 * @see client/jsonapi/review/decorators/local
	 */

	/** client/jsonapi/review/decorators/global
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
	 *  client/jsonapi/review/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
	 * "review" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2020.10
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/review/decorators/excludes
	 * @see client/jsonapi/review/decorators/local
	 */

	/** client/jsonapi/review/decorators/local
	 * Adds a list of local decorators only to the JsonApi client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Client\JsonApi\Review\Decorator\*") around the JsonApi
	 * client.
	 *
	 *  client/jsonapi/review/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Client\JsonApi\Review\Decorator\Decorator2" only to the
	 * "review" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2020.10
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/review/decorators/excludes
	 * @see client/jsonapi/review/decorators/global
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
			if( $view->param( 'aggregate' ) != '' ) {
				$response = $this->aggregate( $view, $request, $response );
			} elseif( $view->param( 'id' ) != '' ) {
				$response = $this->getItem( $view, $request, $response );
			} else {
				$response = $this->getItems( $view, $request, $response );
			}

			$status = 200;
		}
		catch( \Aimeos\Controller\Frontend\Exception $e )
		{
			$status = 403;
			$view->errors = $this->getErrorDetails( $e, 'controller/frontend' );
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

		return $this->render( $response, $view, $status );
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
		$view->attributes = [];

		$view->filter = [
			'f_domain' => [
				'label' => 'Return reviews for that domain, e.g. "product"',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'f_refid' => [
				'label' => 'Return reviews for the ID of the specified domain',
				'type' => 'string', 'default' => '', 'required' => true,
			],
		];

		$view->sort = [
			'ctime' => [
				'label' => 'Sort reviews by creation date/time',
				'type' => 'string', 'default' => false, 'required' => false,
			],
			'rating' => [
				'label' => 'Sort reviews by rating (ascending, "-rating" for descending)',
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
	 * @param \Aimeos\Base\View\Iface $view View instance
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function aggregate( \Aimeos\Base\View\Iface $view, ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$view->data = $this->getController( $view )->sort()
			->slice( $view->param( 'page/offset', 0 ), $view->param( 'page/limit', 10000 ) )
			->aggregate( $view->param( 'aggregate' ) );

		return $response;
	}


	/**
	 * Returns the initialized product controller
	 *
	 * @param \Aimeos\Base\View\Iface $view View instance
	 * @return \Aimeos\Controller\Frontend\Product\Iface Initialized product controller
	 */
	protected function getController( \Aimeos\Base\View\Iface $view )
	{
		$context = $this->context();
		$cntl = \Aimeos\Controller\Frontend::create( $context, 'review' );

		$cntl->for( $view->param( 'filter/f_domain', 'product' ), $view->param( 'filter/f_refid' ) );

		$params = (array) $view->param( 'filter', [] );
		unset( $params['f_domain'], $params['f_refid'] );

		return $cntl->sort( $view->param( 'sort', '-ctime' ) )->parse( $params )
			->slice( $view->param( 'page/offset', 0 ), $view->param( 'page/limit', 10 ) );
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
		$cntl = \Aimeos\Controller\Frontend::create( $this->context(), 'review' );

		$view->items = $cntl->get( $view->param( 'id' ) );
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
		$total = 0;

		$view->items = $this->getController( $view )->search( $total );
		$view->total = $total;

		return $response;
	}


	/**
	 * Returns the response object with the rendered header and body
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @param \Aimeos\Base\View\Iface $view View instance
	 * @param integer $status HTTP status code
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function render( ResponseInterface $response, \Aimeos\Base\View\Iface $view, $status ) : \Psr\Http\Message\ResponseInterface
	{
		if( $view->param( 'aggregate' ) != '' )
		{
			/** client/jsonapi/review/template-aggregate
			 * Relative path to the review aggregate JSON API template
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
			 * @param string Relative path to the template creating the list of aggregated review counts
			 * @since 2020.10
			 * @category Developer
			 */
			$tplconf = 'client/jsonapi/review/template-aggregate';
			$default = 'aggregate-standard';
		}
		else
		{
			/** client/jsonapi/review/template
			 * Relative path to the review JSON API template
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
			 * @param string Relative path to the template creating the body of the JSON API
			 * @since 2017.03
			 * @category Developer
			 */
			$tplconf = 'client/jsonapi/review/template';
			$default = 'review/standard';
		}

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS' )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
