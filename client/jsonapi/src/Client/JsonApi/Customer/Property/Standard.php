<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Customer\Property;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API customer/property client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard
	extends \Aimeos\Client\JsonApi\Base
	implements \Aimeos\Client\JsonApi\Iface
{
	/**
	 * Deletes the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function delete( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$view = $this->view();

		try
		{
			$body = (string) $request->getBody();
			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' );
			$items = $cntl->uses( ['customer/property'] )->get()->getPropertyItems();

			if( ( $relId = $view->param( 'relatedid' ) ) === null )
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
						throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'ID is missing' ), 400 );
					}

					if( ( $item = $items->get( $entry->id ) ) !== null ) {
						$cntl->deletePropertyItem( $item );
					}
				}

				$cntl->store();
			}
			else
			{
				if( ( $item = $items->get( $relId ) ) !== null ) {
					$cntl->deletePropertyItem( $item )->store();
				}
			}

			$status = 200;
		}
		catch( \Aimeos\Controller\Frontend\Customer\Exception $e )
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
			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' );
			$items = $cntl->uses( ['customer/property'] )->get()->getPropertyItems();

			if( ( $relId = $view->param( 'relatedid' ) ) == null )
			{
				$view->items = $items;
				$view->total = count( $items );
			}
			else
			{
				$view->items = $items->get( $relId );
				$view->total = $items->isEmpty() ? 0 : 1;
			}

			$status = 200;
		}
		catch( \Aimeos\Controller\Frontend\Customer\Exception $e )
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
	 * Updates the resource or the resource list partitially
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function patch( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$view = $this->view();

		try
		{
			$body = (string) $request->getBody();

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data->attributes ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			$status = 404;
			$view->total = 0;
			$relId = $view->param( 'relatedid' );

			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' );
			$items = $cntl->uses( ['customer/property'] )->get()->getPropertyItems();

			if( ( $item = $items->get( $relId ) ) !== null )
			{
				$attributes = (array) $payload->data->attributes;
				$propItem = $item->fromArray( $attributes );
				$cntl->addPropertyItem( $propItem )->store();

				$view->items = $propItem;
				$view->total = 1;
				$status = 200;
			}
		}
		catch( \Aimeos\Controller\Frontend\Customer\Exception $e )
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
	 * Creates or updates the resource or the resource list
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	public function post( ServerRequestInterface $request, ResponseInterface $response ) : \Psr\Http\Message\ResponseInterface
	{
		$view = $this->view();

		try
		{
			$body = (string) $request->getBody();

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			if( !is_array( $payload->data ) ) {
				$payload->data = [$payload->data];
			}

			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' )->uses( ['customer/property'] );

			foreach( $payload->data as $entry )
			{
				if( !isset( $entry->attributes ) ) {
					throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Attributes are missing' ) );
				}

				$propItem = $cntl->createPropertyItem( (array) $entry->attributes );
				$cntl->addPropertyItem( $propItem );
			}

			$view->items = $cntl->store()->get()->getPropertyItems();
			$view->total = count( $view->items );
			$status = 201;
		}
		catch( \Aimeos\Controller\Frontend\Customer\Exception $e )
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

		$view->attributes = [
			'customer.property.type' => [
				'label' => 'Code of the property type',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.property.languageid' => [
				'label' => 'Two or five letter ISO language code, e.g. "de" or "de_CH"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.property.value' => [
				'label' => 'Arbitrary property value',
				'type' => 'string', 'default' => '', 'required' => false,
			],
		];

		$tplconf = 'client/jsonapi/template-options';
		$default = 'options-standard';

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
	 * @param int $status HTTP status code
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function render( ResponseInterface $response, \Aimeos\MW\View\Iface $view, int $status ) : \Psr\Http\Message\ResponseInterface
	{
		/** client/jsonapi/customer/property/template
		 * Relative path to the customer property JSON API template
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
		 * @since 2017.07
		 * @category Developer
		 */
		$tplconf = 'client/jsonapi/customer/property/template';
		$default = 'customer/property/standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'DELETE,GET,OPTIONS,PATCH,POST' )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
