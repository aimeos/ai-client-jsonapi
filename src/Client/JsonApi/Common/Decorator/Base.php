<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2025
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Common\Decorator;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Provides common methods for JSON API client decorators
 *
 * @package Client
 * @subpackage JsonApi
 */
abstract class Base
	extends \Aimeos\Client\JsonApi\Base
	implements \Aimeos\Client\JsonApi\Common\Decorator\Iface
{
	private \Aimeos\Client\JsonApi\Iface $client;


	/**
	 * Initializes the client decorator.
	 *
	 * @param \Aimeos\Client\JsonApi\Iface $client Client object
	 * @param \Aimeos\MShop\ContextIface $context Context object with required objects
	 * @param string $path Name of the client, e.g "product"
	 */
	public function __construct( \Aimeos\Client\JsonApi\Iface $client,
		\Aimeos\MShop\ContextIface $context, string $path )
	{
		parent::__construct( $context, $path );

		$this->client = $client;
	}


	/**
	 * Passes unknown methods to wrapped objects
	 *
	 * @param string $name Name of the method
	 * @param array $param List of method parameter
	 * @return mixed Returns the value of the called method
	 * @throws \Aimeos\Client\JsonApi\Exception If method call failed
	 */
	public function __call( string $name, array $param )
	{
		return @call_user_func_array( array( $this->client, $name ), $param );
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
		return $this->client->delete( $request, $response );
	}


	/**
	 * Returns the requested resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function get( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->client->get( $request, $response );
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
		return $this->client->patch( $request, $response );
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
		return $this->client->post( $request, $response );
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
		return $this->client->put( $request, $response );
	}



	/**
	 * Returns the available REST verbs
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function options( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		return $this->client->options( $request, $response );
	}


	/**
	 * Sets the view object that will generate the admin output.
	 *
	 * @param \Aimeos\Base\View\Iface $view The view object which generates the admin output
	 * @return \Aimeos\Client\JsonApi\Iface Reference to this object for fluent calls
	 */
	public function setView( \Aimeos\Base\View\Iface $view ) : \Aimeos\Client\JsonApi\Iface
	{
		$this->client->setView( $view );
		parent::setView( $view );

		return $this;
	}


	/**
	 * Returns the underlying client object;
	 *
	 * @return \Aimeos\Client\JsonApi\Iface Client object
	 */
	protected function getClient() : \Aimeos\Client\JsonApi\Iface
	{
		return $this->client;
	}
}
