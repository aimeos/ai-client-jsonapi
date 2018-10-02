<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Basket\Address;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API basket/address client
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
	 * @param string $path Name of the client, e.g "basket/address"
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
						throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Type (ID) is missing' ) );
					}

					$this->controller->setAddress( $entry->id, null );
				}
			}
			else
			{
				$this->controller->setAddress( $relId, null );
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
				if( !isset( $entry->id ) || !isset( $entry->attributes ) ) {
					throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Address type or attributes are missing' ) );
				}

				$this->controller->setAddress( $entry->id, (array) $entry->attributes );
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
			'order.address.salutation' => [
				'label' => 'Customer salutation, i.e. "comany" ,"mr", "mrs", "miss" or ""',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.company' => [
				'label' => 'Company name',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.vatid' => [
				'label' => 'VAT ID of the company',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.title' => [
				'label' => 'Title of the customer',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.firstname' => [
				'label' => 'First name of the customer',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.lastname' => [
				'label' => 'Last name of the customer or full name',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'order.address.address1' => [
				'label' => 'First address part like street',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'order.address.address2' => [
				'label' => 'Second address part like house number',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.address3' => [
				'label' => 'Third address part like flat number',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.postal' => [
				'label' => 'Zip code of the city',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.city' => [
				'label' => 'Name of the town/city',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'order.address.state' => [
				'label' => 'Two letter code of the country state',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.countryid' => [
				'label' => 'Two letter ISO country code',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'order.address.languageid' => [
				'label' => 'Two or five letter ISO language code, e.g. "de" or "de_CH"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.telephone' => [
				'label' => 'Telephone number consisting of option leading "+" and digits without spaces',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.telefax' => [
				'label' => 'Faximile number consisting of option leading "+" and digits without spaces',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.email' => [
				'label' => 'E-mail address',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.website' => [
				'label' => 'Web site including "http://" or "https://"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'order.address.longitude' => [
				'label' => 'Longitude of the customer location as float value',
				'type' => 'float', 'default' => '', 'required' => false,
			],
			'order.address.latitude' => [
				'label' => 'Latitude of the customer location as float value',
				'type' => 'float', 'default' => '', 'required' => false,
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
