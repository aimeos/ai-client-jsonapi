<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API common client
 *
 * @package Client
 * @subpackage JsonApi
 */
abstract class Base
	implements \Aimeos\Client\JsonApi\Iface, \Aimeos\MW\Macro\Iface
{
	use \Aimeos\MW\Macro\Traits;


	private $view;
	private $context;
	private $path;


	/**
	 * Initializes the client
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context MShop context object
	 * @param string $path Name of the client separated by slashes, e.g "catalog/lists"
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, string $path )
	{
		$this->context = $context;
		$this->path = $path;
	}


	/**
	 * Deletes the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function delete( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->defaultAction( $request, $response );
	}


	/**
	 * Retrieves the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function get( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->defaultAction( $request, $response );
	}


	/**
	 * Updates the resource or the resource list partitially
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function patch( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->defaultAction( $request, $response );
	}


	/**
	 * Creates or updates the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function post( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->defaultAction( $request, $response );
	}


	/**
	 * Creates or updates the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function put( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->defaultAction( $request, $response );
	}


	/**
	 * Creates or updates the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function options( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->defaultAction( $request, $response );
	}


	/**
	 * Sets the view object that will generate the admin output.
	 *
	 * @param \Aimeos\MW\View\Iface $view The view object which generates the admin output
	 * @return \Aimeos\Client\JsonApi\Iface Reference to this object for fluent calls
	 */
	public function setView( \Aimeos\MW\View\Iface $view ) : \Aimeos\Client\JsonApi\Iface
	{
		$this->view = $view;
		return $this;
	}


	/**
	 * Returns the default response for the resource
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function defaultAction( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$status = 403;
		$view = $this->view();

		$view->errors = array( array(
			'title' => $this->getContext()->translate( 'client/jsonapi', 'Not allowed for this resource' ),
		) );

		/** client/jsonapi/template-error
		 * Relative path to the default JSON API template
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
		 * @param string Relative path to the template creating the body for the JSON API response
		 * @since 2017.02
		 * @category Developer
		 * @see client/jsonapi/template-delete
		 * @see client/jsonapi/template-patch
		 * @see client/jsonapi/template-post
		 * @see client/jsonapi/template-get
		 * @see client/jsonapi/template-options
		 */
		$tplconf = 'client/jsonapi/template-error';
		$default = 'error-standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}


	/**
	 * Returns the context item object
	 *
	 * @return \Aimeos\MShop\Context\Item\Iface Context object
	 */
	protected function getContext() : \Aimeos\MShop\Context\Item\Iface
	{
		return $this->context;
	}


	/**
	 * Returns the translated title and the details of the error
	 *
	 * @param \Exception $e Thrown exception
	 * @param string|null $domain Translation domain
	 * @return array Associative list with "title" and "detail" key (if debug config is enabled)
	 */
	protected function getErrorDetails( \Exception $e, string $domain = null ) : array
	{
		$details = [];

		if( $domain !== null ) {
			$details['title'] = $this->context->translate( $domain, $e->getMessage() );
		} else {
			$details['title'] = $e->getMessage();
		}

		/** client/jsonapi/debug
		 * Send debug information withing responses to clients if an error occurrs
		 *
		 * By default, the Aimeos client JSON REST API won't send any details
		 * besides the error message to the client if an error occurred. This
		 * prevents leaking sensitive information to attackers. For debugging
		 * your requests it's helpful to see the stack strace. If you set this
		 * configuration option to true, the stack trace will be returned too.
		 *
		 * @param boolean True to return the stack trace in JSON response, false for error message only
		 * @since 2017.07
		 * @category Developer
		 */
		if( $this->context->getConfig()->get( 'client/jsonapi/debug', false ) == true ) {
			$details['detail'] = $e->getTraceAsString();
		}

		return [$details]; // jsonapi.org requires a list of error objects
	}


	/**
	 * Returns the path to the client
	 *
	 * @return string Client path, e.g. "product/property"
	 */
	protected function getPath() : string
	{
		return $this->path;
	}


	/**
	 * Initializes the criteria object based on the given parameter
	 *
	 * @param \Aimeos\MW\Criteria\Iface $criteria Criteria object
	 * @param array $params List of criteria data with condition, sorting and paging
	 * @return \Aimeos\MW\Criteria\Iface Initialized criteria object
	 */
	protected function initCriteria( \Aimeos\MW\Criteria\Iface $criteria, array $params ) : \Aimeos\MW\Criteria\Iface
	{
		return $criteria->order( $params['sort'] ?? [] )
			->add( $criteria->parse( $params['filter'] ?? [] ) )
			->slice( $params['page']['offset'] ?? 0, $params['page']['limit'] ?? 25 );
	}


	/**
	 * Returns the available REST verbs and the available parameters
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @param string $allow Allowed HTTP methods
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function getOptionsResponse( ServerRequestInterface $request, ResponseInterface $response, string $allow ) : \Psr\Http\Message\ResponseInterface
	{
		$view = $this->view();

		$tplconf = 'client/jsonapi/template-options';
		$default = 'options-standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', $allow )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( 200 );
	}


	/**
	 * Returns the view object that will generate the admin output.
	 *
	 * @return \Aimeos\MW\View\Iface The view object which generates the admin output
	 */
	protected function view() : \Aimeos\MW\View\Iface
	{
		if( !isset( $this->view ) ) {
			throw new \Aimeos\Admin\JsonAdm\Exception( sprintf( 'No view available' ) );
		}

		return $this->view;
	}
}
