<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Order;

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
		$ref = $view->param( 'include', [] );

		if( is_string( $ref ) ) {
			$ref = explode( ',', $ref );
		}

		try
		{
			$cntl = \Aimeos\Controller\Frontend::create( $this->context(), 'order' )->uses( $ref );

			if( ( $id = $view->param( 'id' ) ) != '' )
			{
				$view->items = $cntl->get( $id );
				$view->total = 1;
			}
			else
			{
				$total = 0;
				$items = $cntl->sort( $view->param( 'sort', '-order.id' ) )
					->slice( $view->param( 'page/offset', 0 ), $view->param( 'page/limit', 48 ) )
					->parse( (array) $view->param( 'filter', [] ) )
					->search( $total );

				$view->items = $items;
				$view->total = $total;
			}

			$status = 200;
		}
		catch( \Aimeos\Controller\Frontend\Exception $e )
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

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data->attributes ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( 'Invalid JSON in body', 400 );
			}

			if( !isset( $payload->data->attributes->{'order.baseid'} ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( 'Required attribute "order.baseid" is missing', 400 );
			}

			$basket = $this->getBasket( $payload->data->attributes->{'order.baseid'} );
			$item = $this->createOrder( $payload->data->attributes->{'order.baseid'} );

			$view->form = $this->getPaymentForm( $basket, $item, (array) $payload->data->attributes );
			$view->items = $item;
			$view->total = 1;

			$status = 201;
		}
		catch( \Aimeos\Client\JsonApi\Exception $e )
		{
			$status = $e->getCode();
			$view->errors = $this->getErrorDetails( $e, 'client/jsonapi' );
		}
		catch( \Aimeos\Controller\Frontend\Exception $e )
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
			'order.baseid' => [
				'label' => 'ID of the stored basket (POST only)',
				'type' => 'string', 'default' => '', 'required' => true,
			],
		];

		$tplconf = 'client/jsonapi/template-options';
		$default = 'options-standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS,POST' )
			->withHeader( 'Cache-Control', 'max-age=300' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( 200 );
	}


	/**
	 * Adds and returns a new order item for the given order base ID
	 *
	 * @param string $baseId Unique order base ID
	 * @return \Aimeos\MShop\Order\Item\Iface New order item
	 */
	protected function createOrder( string $baseId ) : \Aimeos\MShop\Order\Item\Iface
	{
		$context = $this->context();
		$cntl = \Aimeos\Controller\Frontend::create( $context, 'order' );
		$item = $cntl->add( $baseId, ['order.channel' => 'jsonapi'] )->store();

		$context->session()->set( 'aimeos/orderid', $item->getId() );

		return $item;
	}


	/**
	 * Returns the basket object for the given ID
	 *
	 * @param string $basketId Unique order base ID
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Basket object including only the services
	 * @throws \Aimeos\Client\JsonApi\Exception If basket ID is not the same as stored before in the current session
	 */
	protected function getBasket( string $basketId ) : \Aimeos\MShop\Order\Item\Base\Iface
	{
		$context = $this->context();
		$baseId = $context->session()->get( 'aimeos/order.baseid' );

		if( $baseId != $basketId )
		{
			$msg = sprintf( 'No basket for the "order.baseid" ("%1$s") found', $basketId );
			throw new \Aimeos\Client\JsonApi\Exception( $msg, 403 );
		}

		$cntl = \Aimeos\Controller\Frontend::create( $context, 'basket' );

		return $cntl->load( $baseId, ['order/base/service'], false );
	}


	/**
	 * Returns the form helper object for building the payment form in the frontend
	 *
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Saved basket object including payment service object
	 * @param \Aimeos\MShop\Order\Item\Iface $orderItem Saved order item created for the basket object
	 * @param array $attributes Associative list of payment data pairs
	 * @return \Aimeos\MShop\Common\Helper\Form\Iface|null Form object with URL, parameters, etc.
	 * 	or null if no form data is required
	 */
	protected function getPaymentForm( \Aimeos\MShop\Order\Item\Base\Iface $basket,
		\Aimeos\MShop\Order\Item\Iface $orderItem, array $attributes ) : ?\Aimeos\MShop\Common\Helper\Form\Iface
	{
		$view = $this->view();
		$context = $this->context();
		$total = $basket->getPrice()->getValue() + $basket->getPrice()->getCosts();
		$services = $basket->getService( \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT );

		if( $services === [] || $total <= '0.00' && $this->isSubscription( $basket->getProducts() ) === false )
		{
			$cntl = \Aimeos\Controller\Frontend::create( $context, 'order' );
			$cntl->save( $orderItem->setStatusPayment( \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED ) );

			$url = $this->getUrlConfirm( $view, [], ['absoluteUri' => true, 'namespace' => false] );
			return new \Aimeos\MShop\Common\Helper\Form\Standard( $url, 'GET' );
		}

		if( ( $service = reset( $services ) ) !== false )
		{
			$args = array( 'code' => $service->getCode(), 'orderid' => $orderItem->getId() );
			$config = array( 'absoluteUri' => true, 'namespace' => false );
			$urls = array(
				'payment.url-success' => $this->getUrlConfirm( $view, $args, $config ),
				'payment.url-update' => $this->getUrlUpdate( $view, $args, $config ),
			);

			foreach( $service->getAttributeItems() as $item ) {
				$attributes[$item->getCode()] = $item->getValue();
			}

			$serviceCntl = \Aimeos\Controller\Frontend::create( $context, 'service' );
			return $serviceCntl->process( $orderItem, $service->getServiceId(), $urls, $attributes );
		}
	}


	/**
	 * Returns the URL to the confirm page.
	 *
	 * @param \Aimeos\Base\View\Iface $view View object
	 * @param array $params Parameters that should be part of the URL
	 * @param array $config Default URL configuration
	 * @return string URL string
	 */
	protected function getUrlConfirm( \Aimeos\Base\View\Iface $view, array $params, array $config ) : string
	{
		$target = $view->config( 'client/html/checkout/confirm/url/target' );
		$cntl = $view->config( 'client/html/checkout/confirm/url/controller', 'checkout' );
		$action = $view->config( 'client/html/checkout/confirm/url/action', 'confirm' );
		$config = $view->config( 'client/html/checkout/confirm/url/config', $config );

		return $view->url( $target, $cntl, $action, $params, [], $config );
	}


	/**
	 * Returns the URL to the update page.
	 *
	 * @param \Aimeos\Base\View\Iface $view View object
	 * @param array $params Parameters that should be part of the URL
	 * @param array $config Default URL configuration
	 * @return string URL string
	 */
	protected function getUrlUpdate( \Aimeos\Base\View\Iface $view, array $params, array $config ) : string
	{
		$target = $view->config( 'client/html/checkout/update/url/target' );
		$cntl = $view->config( 'client/html/checkout/update/url/controller', 'checkout' );
		$action = $view->config( 'client/html/checkout/update/url/action', 'update' );
		$config = $view->config( 'client/html/checkout/update/url/config', $config );

		return $view->url( $target, $cntl, $action, $params, [], $config );
	}


	/**
	 * Tests if one of the products is a subscription
	 *
	 * @param \Aimeos\Map $products Ordered products implementing \Aimeos\MShop\Order\Item\Base\Product\Iface
	 * @return bool True if at least one product is a subscription, false if not
	 */
	protected function isSubscription( \Aimeos\Map $products ) : bool
	{
		foreach( $products as $orderProduct )
		{
			if( $orderProduct->getAttributeItem( 'interval', 'config' ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Returns the response object with the rendered header and body
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response Response object
	 * @param \Aimeos\Base\View\Iface $view View instance
	 * @param int $status HTTP status code
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function render( ResponseInterface $response, \Aimeos\Base\View\Iface $view, int $status ) : \Psr\Http\Message\ResponseInterface
	{
		/** client/jsonapi/order/template
		 * Relative path to the order JSON API template
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
		 * @param string Relative path to the template creating the body of the JSON API
		 * @since 2017.03
		 * @category Developer
		 */
		$tplconf = 'client/jsonapi/order/template';
		$default = 'order/standard';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS,POST' )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
