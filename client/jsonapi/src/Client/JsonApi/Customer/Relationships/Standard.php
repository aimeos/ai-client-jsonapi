<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Customer\Relationships;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API customer/relationships client
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
			$relId = $view->param( 'relatedid' );
			$ref = ( $inc = $view->param( 'include' ) ) ? explode( ',', $inc ) : [];

			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' );
			$items = $cntl->uses( $ref )->get()->getListItems( $ref );

			if( $relId == null )
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

					if( isset( $items[$entry->id] ) ) {
						$cntl->deleteListItem( $items[$entry->id]->getDomain(), $items[$entry->id] );
					}
				}

				$cntl->store();
			}
			else
			{
				if( isset( $items[$relId] ) ) {
					$cntl->deleteListItem( $items[$relId]->getDomain(), $items[$relId] )->store();
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
			$relId = $view->param( 'relatedid' );
			$ref = ( $inc = $view->param( 'include' ) ) ? explode( ',', $inc ) : [];

			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' );
			$items = $cntl->uses( $ref )->get()->getListItems( $ref );

			if( $relId === null )
			{
				$view->items = $items;
				$view->total = count( $items );
			}
			else
			{
				$view->items = isset( $items[$relId] ) ? $items[$relId] : null;
				$view->total = empty( $view->items ) ? 0 : 1;
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
			$ref = ( $inc = $view->param( 'include' ) ) ? explode( ',', $inc ) : [];

			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' );
			$items = $cntl->uses( $ref )->get()->getListItems( $ref );

			if( isset( $items[$relId] ) )
			{
				$attributes = (array) $payload->data->attributes;
				$listItem = $items[$relId]->fromArray( $attributes );
				$cntl->addListItem( $listItem->getDomain(), $listItem )->store();

				$view->items = $listItem;
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
			$ref = ( $inc = $view->param( 'include' ) ) ? explode( ',', $inc ) : [];
			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'customer' )->uses( $ref );

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			if( !is_array( $payload->data ) ) {
				$payload->data = [$payload->data];
			}

			foreach( $payload->data as $entry )
			{
				if( !isset( $entry->attributes ) ) {
					throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Attributes are missing' ) );
				}

				$listItem = $cntl->createListItem( (array) $entry->attributes );
				$cntl->addListItem( $listItem->getDomain(), $listItem );
			}

			$view->items = $cntl->store()->get()->getListItems( $ref );
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
			'customer.lists.refid' => [
				'label' => 'ID of the related domain item',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.lists.domain' => [
				'label' => 'Domain of the related item, e.g. "product"',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.lists.type' => [
				'label' => 'Customer relationship type, e.g. "favorite"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.lists.config' => [
				'label' => 'Associative list of key/value configuration pairs',
				'type' => 'string', 'default' => '[]', 'required' => false,
			],
			'customer.lists.datestart' => [
				'label' => 'Start date when the relationship is valied',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.lists.dateend' => [
				'label' => 'End date until the relationship is valid',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.lists.status' => [
				'label' => 'Status of the relationship (0=disable, 1=enabled)',
				'type' => 'string', 'default' => '1', 'required' => false,
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
		/** client/jsonapi/customer/relationships/template
		 * Relative path to the customer relationships JSON API template
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
		$tplconf = 'client/jsonapi/customer/relationships/template';
		$default = 'customer/relationships/standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'DELETE,GET,OPTIONS,PATCH,POST' )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
