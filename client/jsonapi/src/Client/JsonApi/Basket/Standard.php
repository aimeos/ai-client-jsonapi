<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Basket;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API basket client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard extends Base implements \Aimeos\Client\JsonApi\Iface
{
	private $controller;


	/**
	 * Initializes the client
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context MShop context object
	 * @param string $path Name of the client, e.g "basket"
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, $path )
	{
		parent::__construct( $context, $path );

		$this->controller = \Aimeos\Controller\Frontend\Basket\Factory::createController( $this->getContext() );
	}


	/**
	 * Deletes the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function delete( ServerRequestInterface $request, ResponseInterface $response )
	{
		$view = $this->getView();

		try
		{
			$this->clearCache();

			$status = 200;
			$type = $view->param( 'id', 'default' );
			$view->item = $this->controller->setType( $type )->clear()->get();
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$status = 404;
			$view->errors = $this->getErrorDetails( $e, 'mshop' );
		}
		catch( \Exception $e )
		{
			$status = 500;
			$view->errors = $this->getErrorDetails( $e );
		}

		return $this->render( $response, $view, $status );
	}


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

		$allow = false;
		$id = $view->param( 'id', 'default' );

		try
		{
			try
			{
				$view->item = $this->controller->load( $id, $this->getParts( $view ) );
			}
			catch( \Aimeos\MShop\Exception $e )
			{
				$view->item = $this->controller->setType( $id )->get();
				$allow = true;
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
			$status = 500;
			$view->errors = $this->getErrorDetails( $e );
		}

		return $this->render( $response, $view, $status, $allow );
	}


	/**
	 * Updates the resource or the resource list partitially
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function patch( ServerRequestInterface $request, ResponseInterface $response )
	{
		$view = $this->getView();

		try
		{
			$this->clearCache();

			$body = (string) $request->getBody();

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data->attributes ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			$basket = $this->controller->setType( $view->param( 'id', 'default' ) )->get();

			if( isset( $payload->data->attributes->{'order.base.comment'} ) ) {
				$basket->setComment( $payload->data->attributes->{'order.base.comment'} );
			}

			$view->item = $basket;
			$status = 200;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$status = 404;
			$view->errors = $this->getErrorDetails( $e, 'mshop' );
		}
		catch( \Exception $e )
		{
			$status = 500;
			$view->errors = $this->getErrorDetails( $e );
		}

		return $this->render( $response, $view, $status );
	}


	/**
	 * Creates or updates the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function post( ServerRequestInterface $request, ResponseInterface $response )
	{
		$view = $this->getView();

		try
		{
			$this->clearCache();

			$item = $this->controller->setType( $view->param( 'id', 'default' ) )->store();
			$this->getContext()->getSession()->set( 'aimeos/order.baseid', $item->getId() );

			$view->item = $item;
			$status = 200;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$status = 404;
			$view->errors = $this->getErrorDetails( $e, 'mshop' );
		}
		catch( \Exception $e )
		{
			$status = 500;
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
	public function options( ServerRequestInterface $request, ResponseInterface $response )
	{
		$view = $this->getView();

		$view->attributes = [
			'order.base.comment' => [
				'label' => 'Customer comment for the order',
				'type' => 'string', 'default' => '', 'required' => false,
			],
		];

		$tplconf = 'client/jsonapi/standard/template-options';
		$default = 'options-standard.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'DELETE,GET,OPTIONS,PATCH,POST' )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( 200 );
	}


	/**
	 * Returns the integer constant for the basket parts that should be included
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @return integer Constant from Aimeos\MShop\Order\Item\Base\Base
	 */
	protected function getParts( \Aimeos\MW\View\Iface $view )
	{
		$available = array(
			'basket/address' => \Aimeos\MShop\Order\Item\Base\Base::PARTS_ADDRESS,
			'basket/coupon' => \Aimeos\MShop\Order\Item\Base\Base::PARTS_COUPON,
			'basket/product' => \Aimeos\MShop\Order\Item\Base\Base::PARTS_PRODUCT,
			'basket/service' => \Aimeos\MShop\Order\Item\Base\Base::PARTS_SERVICE,
		);

		$included = explode( ',', $view->param( 'included', 'basket/address,basket/coupon,basket/product,basket/service' ) );

		$parts = \Aimeos\MShop\Order\Item\Base\Base::PARTS_NONE;
		foreach( $included as $type )
		{
			if( isset( $available[$type] ) ) {
				$parts |= $available[$type];
			}
		}

		return $parts;
	}


	/**
	 * Returns the response object with the rendered header and body
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param integer $status HTTP status code
	 * @param boolean $allow True to allow all HTTP methods, false for GET only
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function render( ResponseInterface $response, \Aimeos\MW\View\Iface $view, $status, $allow = true )
	{
		/** client/jsonapi/basket/standard/template
		 * Relative path to the basket JSON API template
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
		 * @param string Relative path to the template creating the body for the JSON API
		 * @since 2017.04
		 * @category Developer
		 */
		$tplconf = 'client/jsonapi/basket/standard/template';
		$default = 'basket/standard.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		if( $allow === true ) {
			$methods = 'DELETE,GET,OPTIONS,PATCH,POST';
		} else {
			$methods = 'GET';
		}

		return $response->withHeader( 'Allow', $methods )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
