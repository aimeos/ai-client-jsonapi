<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * JSON API standard client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Standard
	extends \Aimeos\Client\JsonApi\Base
	implements \Aimeos\Client\JsonApi\Iface
{
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
			$ref = $view->param( 'include', ['media', 'price', 'text'] );

			if( is_string( $ref ) ) {
				$ref = explode( ',', $ref );
			}

			$cntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'service' )->uses( $ref );
			$basketCntl = \Aimeos\Controller\Frontend::create( $this->getContext(), 'basket' );
			$basket = $basketCntl->get();

			if( ( $id = $view->param( 'id' ) ) != '' )
			{
				$provider = $cntl->getProvider( $id );

				if( $provider->isAvailable( $basket ) === true )
				{
					$view->prices = map( [$id => $provider->calcPrice( $basket )] );
					$view->attributes = [$id => $provider->getConfigFE( $basket )];
					$view->items = $provider->getServiceItem();
					$view->total = 1;
				}
			}
			else
			{
				$attributes = [];
				$items = map();
				$prices = map();
				$cntl->type( $view->param( 'filter/cs_type' ) );

				foreach( $cntl->getProviders() as $id => $provider )
				{
					if( $provider->isAvailable( $basket ) === true )
					{
						$attributes[$id] = $provider->getConfigFE( $basket );
						$prices[$id] = $provider->calcPrice( $basket );
						$items[$id] = $provider->getServiceItem();
					}
				}

				$view->attributes = $attributes;
				$view->prices = $prices;
				$view->items = $items;
				$view->total = count( $items );
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
			$status = $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500;
			$view->errors = $this->getErrorDetails( $e );
		}

		/** client/jsonapi/service/template
		 * Relative path to the service JSON API template
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
		 * @param string Relative path to the template creating the body for the GET method of the JSON API
		 * @since 2017.03
		 * @category Developer
		 */
		$tplconf = 'client/jsonapi/service/template';
		$default = 'service/standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS' )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
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

		$view->filter = [
			'cs_type' => [
				'label' => 'Type of the service items that should be returned ("delivery" or "payment")',
				'type' => 'string', 'default' => '', 'required' => false,
			],
		];

		$tplconf = 'client/jsonapi/template-options';
		$default = 'options-standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS' )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( 200 );
	}
}
