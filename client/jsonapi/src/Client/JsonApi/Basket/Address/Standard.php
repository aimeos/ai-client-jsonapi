<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
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
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, string $path )
	{
		parent::__construct( $context, $path );

		$this->controller = \Aimeos\Controller\Frontend\Basket\Factory::create( $this->getContext() );
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
		$view = $this->view();

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

					$this->controller->deleteAddress( $entry->id );
				}
			}
			else
			{
				$this->controller->deleteAddress( $relId );
			}


			$view->item = $this->controller->get();
			$status = 200;
		}
		catch( \Aimeos\MShop\Plugin\Provider\Exception $e )
		{
			$status = 409;
			$errors = $this->translatePluginErrorCodes( $e->getErrorCodes() );
			$view->errors = $this->getErrorDetails( $e, 'mshop' ) + $errors;
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

				$this->controller->addAddress( $entry->id, (array) $entry->attributes );
			}


			$view->item = $this->controller->get();
			$status = 201;
		}
		catch( \Aimeos\MShop\Plugin\Provider\Exception $e )
		{
			$status = 409;
			$errors = $this->translatePluginErrorCodes( $e->getErrorCodes() );
			$view->errors = $this->getErrorDetails( $e, 'mshop' ) + $errors;
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
			'addressid' => [
				'label' => 'ID of the customer address',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'salutation' => [
				'label' => 'Customer salutation, i.e. "comany" ,"mr", "ms" or ""',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'company' => [
				'label' => 'Company name',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'vatid' => [
				'label' => 'VAT ID of the company',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'title' => [
				'label' => 'Title of the customer',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'firstname' => [
				'label' => 'First name of the customer',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'lastname' => [
				'label' => 'Last name of the customer or full name',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'address1' => [
				'label' => 'First address part like street',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'address2' => [
				'label' => 'Second address part like house number',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'address3' => [
				'label' => 'Third address part like flat number',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'postal' => [
				'label' => 'Zip code of the city',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'city' => [
				'label' => 'Name of the town/city',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'state' => [
				'label' => 'Two letter code of the country state',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'countryid' => [
				'label' => 'Two letter ISO country code',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'languageid' => [
				'label' => 'Two or five letter ISO language code, e.g. "de" or "de_CH"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'telephone' => [
				'label' => 'Telephone number consisting of optional leading "+" and digits without spaces',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'telefax' => [
				'label' => 'Facsimile number consisting of optional leading "+" and digits without spaces',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'email' => [
				'label' => 'E-mail address',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'website' => [
				'label' => 'Web site including "http://" or "https://"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'longitude' => [
				'label' => 'Longitude of the customer location as float value',
				'type' => 'float', 'default' => '', 'required' => false,
			],
			'latitude' => [
				'label' => 'Latitude of the customer location as float value',
				'type' => 'float', 'default' => '', 'required' => false,
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
		$tplconf = 'client/jsonapi/basket/template';
		$default = 'basket/standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'DELETE,GET,OPTIONS,PATCH,POST' )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
