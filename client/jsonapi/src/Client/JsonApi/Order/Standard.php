<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
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
	public function get( ServerRequestInterface $request, ResponseInterface $response )
	{
		$view = $this->getView();

		try
		{
			$cntl = \Aimeos\Controller\Frontend\Factory::createController( $this->getContext(), 'order' );

			if( ( $id = $view->param( 'id' ) ) != '' )
			{
				$view->items = $cntl->getItem( $id );
				$view->total = 1;
			}
			else
			{
				$total = 0;
				$filter = $cntl->createFilter();
				$this->initCriteria( $filter, $view->param() );

				$view->items = $cntl->searchItems( $filter, $total );
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
			$body = (string) $request->getBody();

			if( ( $payload = json_decode( $body ) ) === null || !isset( $payload->data->attributes ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid JSON in body' ), 400 );
			}

			if( !isset( $payload->data->attributes->{'order.baseid'} ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Required attribute "order.baseid" is missing' ), 400 );
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
			'order.baseid' => [
				'label' => 'ID of the stored basket (POST only)',
				'type' => 'string', 'default' => '', 'required' => true,
			],
		];

		$tplconf = 'client/jsonapi/standard/template-options';
		$default = 'options-standard.php';

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
	protected function createOrder( $baseId )
	{
		$context = $this->getContext();
		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $context, 'order' );

		$item = $cntl->addItem( $baseId, 'jsonapi' );
		$cntl->block( $item );

		$context->getSession()->set( 'aimeos/orderid', $item->getId() );

		return $item;
	}


	/**
	 * Returns the basket object for the given ID
	 *
	 * @param string $basketId Unique order base ID
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Basket object including only the services
	 * @throws \Aimeos\Client\JsonApi\Exception If basket ID is not the same as stored before in the current session
	 */
	protected function getBasket( $basketId )
	{
		$context = $this->getContext();
		$baseId = $context->getSession()->get( 'aimeos/order.baseid' );

		if( $baseId != $basketId )
		{
			$msg = sprintf( 'No basket for the "order.baseid" ("%1$s") found', $basketId );
			throw new \Aimeos\Client\JsonApi\Exception( $msg, 403 );
		}

		$parts = \Aimeos\MShop\Order\Item\Base\Base::PARTS_SERVICE;
		$cntl = \Aimeos\Controller\Frontend\Factory::createController( $context, 'basket' );

		return $cntl->load( $baseId, $parts, false );
	}


	/**
	 * Returns the form helper object for building the payment form in the frontend
	 *
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Saved basket object including payment service object
	 * @param \Aimeos\MShop\Order\Item\Iface $orderItem Saved order item created for the basket object
	 * @param array $attributes Associative list of payment data pairs
	 * @return \Aimeos\MShop\Common\Item\Helper\Form\Iface|null Form object with URL, parameters, etc.
	 * 	or null if no form data is required
	 */
	protected function getPaymentForm( \Aimeos\MShop\Order\Item\Base\Iface $basket,
		\Aimeos\MShop\Order\Item\Iface $orderItem, array $attributes )
	{
		$view = $this->getView();
		$context = $this->getContext();
		$total = $basket->getPrice()->getValue() + $basket->getPrice()->getCosts();
		$services = $basket->getService( \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT );

		if( $services === [] || $total <= '0.00' && $this->isSubscription( $basket->getProducts() ) === false )
		{
			$orderCntl = \Aimeos\Controller\Frontend\Factory::createController( $context, 'order' );
			$orderCntl->saveItem( $orderItem->setPaymentStatus( \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED ) );

			$url = $this->getUrlConfirm( $view, [], ['absoluteUri' => true, 'namespace' => false] );
			return new \Aimeos\MShop\Common\Item\Helper\Form\Standard( $url, 'GET' );
		}

		if( ( $service = reset( $services ) ) !== false )
		{
			$args = array( 'code' => $service->getCode(), 'orderid' => $orderItem->getId() );
			$config = array( 'absoluteUri' => true, 'namespace' => false );
			$urls = array(
				'payment.url-success' => $this->getUrlConfirm( $view, $args, $config ),
				'payment.url-update' => $this->getUrlUpdate( $view, $args, $config ),
			);

			foreach( $service->getAttributes() as $item ) {
				$attributes[$item->getCode()] = $item->getValue();
			}

			$serviceCntl = \Aimeos\Controller\Frontend\Factory::createController( $context, 'service' );
			return $serviceCntl->process( $orderItem, $service->getServiceId(), $urls, $attributes );
		}
	}


	/**
	 * Returns the URL to the confirm page.
	 *
	 * @param \Aimeos\MW\View\Iface $view View object
	 * @param array $params Parameters that should be part of the URL
	 * @param array $config Default URL configuration
	 * @return string URL string
	 */
	protected function getUrlConfirm( \Aimeos\MW\View\Iface $view, array $params, array $config )
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
	 * @param \Aimeos\MW\View\Iface $view View object
	 * @param array $params Parameters that should be part of the URL
	 * @param array $config Default URL configuration
	 * @return string URL string
	 */
	protected function getUrlUpdate( \Aimeos\MW\View\Iface $view, array $params, array $config )
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
	 * @param \Aimeos\MShop\Order\Item\Base\Product\Iface[] $products Ordered products
	 * @return boolean True if at least one product is a subscription, false if not
	 */
	protected function isSubscription( array $products )
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
	 * @param \Aimeos\MW\View\Iface $view View instance
	 * @param integer $status HTTP status code
	 * @return \Psr\Http\Message\ResponseInterface Modified response object
	 */
	protected function render( ResponseInterface $response, \Aimeos\MW\View\Iface $view, $status )
	{
		/** client/jsonapi/order/standard/template
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
		$tplconf = 'client/jsonapi/order/standard/template';
		$default = 'order/standard.php';

		$body = $view->render( $view->config( $tplconf, $default ) );

		return $response->withHeader( 'Allow', 'GET,OPTIONS,POST' )
			->withHeader( 'Cache-Control', 'no-cache, private' )
			->withHeader( 'Content-Type', 'application/vnd.api+json' )
			->withBody( $view->response()->createStreamFromString( $body ) )
			->withStatus( $status );
	}
}
