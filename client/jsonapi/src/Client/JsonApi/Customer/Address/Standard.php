<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Customer\Address;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API customer/address client
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
	 * @param string $path Name of the client, e.g "customer/address"
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, $path )
	{
		parent::__construct( $context, $path );

		$this->controller = \Aimeos\Controller\Frontend\Customer\Factory::createController( $this->getContext() );
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
						throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'ID is missing' ), 400 );
					}

					$this->controller->deleteAddressItem( $entry->id );
				}
			}
			else
			{
				$this->controller->deleteAddressItem( $relId );
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

		try
		{
			$relId = $view->param( 'relatedid' );
			$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'customer' );

			if( $relId == null )
			{
				$view->items = $cntl->getItem( $view->param( 'id' ), ['customer/address'] )->getAddressItems();
				$view->total = count( $view->items );
			}
			else
			{
				$view->items = $cntl->getAddressItem( $relId );
				$view->total = 1;
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
			$body = (string) $request->getBody();

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data->attributes ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'customer' );

			$view->items = $cntl->editAddressItem( $view->param( 'relatedid' ), (array) $payload->data->attributes );
			$view->total = 1;
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
			$list = [];
			$body = (string) $request->getBody();

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

				$list[] = $this->controller->addAddressItem( (array) $entry->attributes );
			}


			$view->total = count( $list );
			$view->items = $list;
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
			'customer.address.salutation' => [
				'label' => 'Customer salutation, i.e. "comany" ,"mr", "mrs", "miss" or ""',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.company' => [
				'label' => 'Company name',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.vatid' => [
				'label' => 'VAT ID of the company',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.title' => [
				'label' => 'Title of the customer',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.firstname' => [
				'label' => 'First name of the customer',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.lastname' => [
				'label' => 'Last name of the customer or full name',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.address.address1' => [
				'label' => 'First address part like street',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.address.address2' => [
				'label' => 'Second address part like house number',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.address3' => [
				'label' => 'Third address part like flat number',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.postal' => [
				'label' => 'Zip code of the city',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.city' => [
				'label' => 'Name of the town/city',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.address.state' => [
				'label' => 'Two letter code of the country state',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.countryid' => [
				'label' => 'Two letter ISO country code',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.address.languageid' => [
				'label' => 'Two or five letter ISO language code, e.g. "de" or "de_CH"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.telephone' => [
				'label' => 'Telephone number consisting of option leading "+" and digits without spaces',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.telefax' => [
				'label' => 'Faximile number consisting of option leading "+" and digits without spaces',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.email' => [
				'label' => 'E-mail address',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.website' => [
				'label' => 'Web site including "http://" or "https://"',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.longitude' => [
				'label' => 'Longitude of the customer location as float value',
				'type' => 'float', 'default' => '', 'required' => false,
			],
			'customer.address.latitude' => [
				'label' => 'Latitude of the customer location as float value',
				'type' => 'float', 'default' => '', 'required' => false,
			],
			'customer.address.label' => [
				'label' => 'Label to identify the customer, usually the full name',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.address.code' => [
				'label' => 'Unique customer identifier, usually e-mail address',
				'type' => 'string', 'default' => '', 'required' => true,
			],
			'customer.address.birthday' => [
				'label' => 'ISO date in YYYY-MM-DD format of the birthday',
				'type' => 'string', 'default' => '', 'required' => false,
			],
			'customer.address.status' => [
				'label' => 'Customer account status, i.e. "0" for disabled, "1" for enabled',
				'type' => 'integer', 'default' => '1', 'required' => false,
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
		/** client/jsonapi/customer/address/standard/template
		 * Relative path to the customer address JSON API template
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
		$tplconf = 'client/jsonapi/customer/address/standard/template';
		$default = 'customer/address/standard.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'DELETE,GET,OPTIONS,PATCH,POST' )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
