<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
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
	extends \Aimeos\Client\JsonApi\Basket\Base
	implements \Aimeos\Client\JsonApi\Iface
{
	private $controller;


	/**
	 * Initializes the client
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context MShop context object
	 * @param string $path Name of the client, e.g "basket/product"
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
			$this->controller->setType( $view->param( 'id', 'default' ) );

			$relId = $view->param( 'relatedid' );
			$body = (string) $request->getBody();

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


			$view->item = $this->controller->get();
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
			$this->controller->setType( $view->param( 'id', 'default' ) );

			$body = (string) $request->getBody();
			$relId = $view->param( 'relatedid' );

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

			$view->item = $this->controller->get();
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
			$this->controller->setType( $view->param( 'id', 'default' ) );

			$body = (string) $request->getBody();

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			if( !is_array( $payload->data ) ) {
				$payload->data = [$payload->data];
			}

			foreach( $payload->data as $entry )
			{
				if( !isset( $entry->attributes ) || !isset( $entry->attributes->{'product.id'} ) ) {
					throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Product ID is missing' ) );
				}

				$qty = ( isset( $entry->attributes->quantity ) ? $entry->attributes->quantity : 1 );
				$stocktype = ( isset( $entry->attributes->stocktype ) ? $entry->attributes->stocktype : 'default' );
				$variantAttrIds = ( isset( $entry->attributes->variant ) ? (array) $entry->attributes->variant : [] );
				$hiddenAttrIds = ( isset( $entry->attributes->hidden ) ? (array) $entry->attributes->hidden : [] );
				$configAttrIds = ( isset( $entry->attributes->config ) ? get_object_vars( $entry->attributes->config ) : [] );
				$customAttrIds = ( isset( $entry->attributes->custom ) ? get_object_vars( $entry->attributes->custom ) : [] );

				$this->controller->addProduct( $entry->attributes->{'product.id'}, $qty, $stocktype,
					$variantAttrIds, $configAttrIds, $hiddenAttrIds, $customAttrIds );
			}


			$view->item = $this->controller->get();
			$status = 201;
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
			'product.id' => [
				'label' => 'Product ID from article, bundle or selection product (POST only)',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'quantity' => [
				'label' => 'Number of product items (POST only)',
				'type' => 'string', 'default' => '1', 'required' => false,
			],
			'stocktype' => [
				'label' => 'Code of the warehouse/location type (POST only)',
				'type' => 'string', 'default' => 'default', 'required' => false,
			],
			'variant' => [
				'label' => 'List of attribute IDs of the selected variant attributes (POST only)',
				'type' => 'array', 'default' => '[]', 'required' => false,
			],
			'config' => [
				'label' => 'List of attribute IDs of the selected config attributes (POST only)',
				'type' => 'array', 'default' => '[]', 'required' => false,
			],
			'hidden' => [
				'label' => 'List of attribute IDs of the hidden product attributes that will be added but should be invisible (POST only)',
				'type' => 'array', 'default' => '[]', 'required' => false,
			],
			'custom' => [
				'label' => 'List of values entered by the user for the custom attributes with the attribute IDs as keys (POST only)',
				'type' => 'array[<attrid>]', 'default' => '[]', 'required' => false,
			],
			'codes' => [
				'label' => 'List of product options (added via "config") that should be removed (PATCH only)',
				'type' => 'array', '' => '[]', 'required' => false,
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
		$default = 'basket/standard.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'DELETE,GET,OPTIONS,PATCH,POST' )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
