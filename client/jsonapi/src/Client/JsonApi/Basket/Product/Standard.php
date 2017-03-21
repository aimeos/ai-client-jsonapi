<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Basket\Product;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API basket/product client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard
	extends \Aimeos\Client\JsonApi\Base
	implements \Aimeos\Client\JsonApi\Iface
{
	private $controller;


	/**
	 * Initializes the client
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context MShop context object
	 * @param \Aimeos\MW\View\Iface $view View object
	 * @param array $templatePaths List of file system paths where the templates are stored
	 * @param string $path Name of the client, e.g "basket/product"
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MW\View\Iface $view, array $templatePaths, $path )
	{
		parent::__construct( $context, $view, $templatePaths, $path );

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
			$relId = $view->param( 'relatedid' );
			$body = (string) $request->getBody();
			$this->controller->setType( $view->param( 'id', 'default' ) );

			if( $relId === '' || $relId === null )
			{
				if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data ) ) {
					throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
				}

				if( !is_array( $payload->data ) ) {
					$payload->data = [$payload->data];
				}

				foreach( $payload->data as $entry )
				{
					if( !isset( $entry->id ) ) {
						throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Position (ID) is missing' ) );
					}

					$this->controller->deleteProduct( $entry->id );
				}
			}
			else
			{
				$this->controller->deleteProduct( $relId );
			}


			$view->items = $this->controller->get();
			$view->total = 1;

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
		$methods = 'GET';
		$view = $this->getView();
		$relId = $view->param( 'relatedid' );
		$id = $view->param( 'id', 'default' );

		try
		{
			try
			{
				$basket = $this->controller->load( $id, \Aimeos\MShop\Order\Manager\Base\Base::PARTS_PRODUCT );

				if( $relId !== '' && $relId !== null )
				{
					foreach( $basket->getProducts() as $orderProduct )
					{
						if( $orderProduct->getId() == $relId )
						{
							$view->items = $orderProduct;
							break;
						}
					}
				}
			}
			catch( \Aimeos\MShop\Exception $e )
			{
				$basket = $this->controller->setType( $id )->get();
				$methods = 'DELETE,GET,PATCH,POST';

				if( $relId !== '' && $relId !== null ) {
					$view->items = $basket->getProduct( $relId );
				}
			}

			if( $relId === '' || $relId === null ) {
				$view->items = $basket->getProducts();
			}

			$view->total = count( $view->items );
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

		/** client/jsonapi/basket/product/standard/template
		 * Relative path to the basket/product JSON API template
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
		 * @param string Relative path to the template creating the body for the JSON API
		 * @since 2017.04
		 * @category Developer
		 */
		$tplconf = 'client/jsonapi/basket/product/standard/template';
		$default = 'basket/product-default.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', $methods )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
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
			$body = (string) $request->getBody();
			$relId = $view->param( 'relatedid' );
			$this->controller->setType( $view->param( 'id', 'default' ) );

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data ) || !isset( $payload->data->attributes ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			if( !is_array( $payload->data ) ) {
				$payload->data = [$payload->data];
			}

			foreach( $payload->data as $entry )
			{
				if( $relId !== '' && $relId !== null ) {
					$entry->id = $relId;
				}

				if( !isset( $entry->id ) ) {
					throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Position (ID) is missing' ) );
				}

				$qty = ( isset( $entry->attributes->quantity ) ? $entry->attributes->quantity : 1 );
				$cfgAttrCodes = ( isset( $entry->attributes->codes ) ? (array) $entry->attributes->codes : [] );

				$this->controller->editProduct( $entry->id, $qty, $cfgAttrCodes );
			}

			$view->items = $this->controller->get();
			$view->total = 1;

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
			$body = (string) $request->getBody();
			$this->controller->setType( $view->param( 'id', 'default' ) );

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			if( !is_array( $payload->data ) ) {
				$payload->data = [$payload->data];
			}

			foreach( $payload->data as $entry )
			{
				if( !isset( $entry->attributes ) || !isset( $entry->attributes->productid ) ) {
					throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Product ID is missing' ) );
				}

				$qty = ( isset( $entry->attributes->quantity ) ? $entry->attributes->quantity : 1 );
				$stocktype = ( isset( $entry->attributes->stocktype ) ? $entry->attributes->stocktype : 'default' );
				$variantAttrIds = ( isset( $entry->attributes->variant ) ? (array) $entry->attributes->variant : [] );
				$configAttrIds = ( isset( $entry->attributes->config ) ? (array) $entry->attributes->config : [] );
				$hiddenAttrIds = ( isset( $entry->attributes->hidden ) ? (array) $entry->attributes->hidden : [] );
				$customAttrIds = ( isset( $entry->attributes->custom ) ? (array) $entry->attributes->custom : [] );

				$this->controller->addProduct( $entry->attributes->productid, $qty, [],
					$variantAttrIds, $configAttrIds, $hiddenAttrIds, $customAttrIds, $stocktype );
			}


			$view->items = $this->controller->get();
			$view->total = 1;

			$status = 201;
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

		return $this->render( $response, $view, $status );
	}


	/**
	 * Returns the response object with the rendered header and body
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param integer $status HTTP status code
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function render( ResponseInterface $response, \Aimeos\MW\View\Iface $view, $status )
	{
		$tplconf = 'client/jsonapi/basket/standard/template';
		$default = 'basket/default.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'DELETE,GET,PATCH,POST' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
