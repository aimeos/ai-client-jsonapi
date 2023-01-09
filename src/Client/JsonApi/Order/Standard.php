<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
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
	/** client/jsonapi/order/name
	 * Class name of the used order client implementation
	 *
	 * Each default JSON API client can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the client factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Client\JsonApi\Order\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Client\JsonApi\Order\Myorder
	 *
	 * then you have to set the this configuration option:
	 *
	 *  client/jsonapi/order/name = Myorder
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyOrder"!
	 *
	 * @param string Last part of the class name
	 * @since 2017.03
	 * @category Developer
	 */

	/** client/jsonapi/order/decorators/excludes
	 * Excludes decorators added by the "common" option from the JSON API clients
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "client/jsonapi/common/decorators/default" before they are wrapped
	 * around the JsonApi client.
	 *
	 *  client/jsonapi/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Client\JsonApi\Common\Decorator\*") added via
	 * "client/jsonapi/common/decorators/default" for the JSON API client.
	 *
	 * @param array List of decorator names
	 * @since 2017.07
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/order/decorators/global
	 * @see client/jsonapi/order/decorators/local
	 */

	/** client/jsonapi/order/decorators/global
	 * Adds a list of globally available decorators only to the JsonApi client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Client\JsonApi\Common\Decorator\*") around the JsonApi
	 * client.
	 *
	 *  client/jsonapi/order/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
	 * "order" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2017.07
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/order/decorators/excludes
	 * @see client/jsonapi/order/decorators/local
	 */

	/** client/jsonapi/order/decorators/local
	 * Adds a list of local decorators only to the JsonApi client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Client\JsonApi\Order\Decorator\*") around the JsonApi
	 * client.
	 *
	 *  client/jsonapi/order/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Client\JsonApi\Order\Decorator\Decorator2" only to the
	 * "order" JsonApi client.
	 *
	 * @param array List of decorator names
	 * @since 2017.07
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/order/decorators/excludes
	 * @see client/jsonapi/order/decorators/global
	 */


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
			$ref = explode( ',', str_replace( '.', '/', $ref ) );
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

			if( !isset( $payload->data->attributes->{'order.id'} ) ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'No order ID found' ), 400 );
			}

			$item = $this->getOrder( $payload->data->attributes->{'order.id'} );

			$view->form = $this->getPaymentForm( $item, (array) $payload->data->attributes );
			$view->items = $item;
			$view->total = 1;

			\Aimeos\Controller\Frontend::create( $this->context(), 'order' )->save( $item );
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
			'order.id' => [
				'label' => 'ID of the stored basket/order (POST only)',
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
	 * Returns the order object for the given ID
	 *
	 * @param string $orderId Unique order ID
	 * @return \Aimeos\MShop\Order\Item\Iface Order object including only the services
	 * @throws \Aimeos\Client\JsonApi\Exception If basket ID is not the same as stored before in the current session
	 */
	protected function getOrder( string $orderId ) : \Aimeos\MShop\Order\Item\Iface
	{
		$context = $this->context();
		$id = $context->session()->get( 'aimeos/order.id' );

		if( $id != $orderId )
		{
			$msg = sprintf( 'No order for the "order.id" ("%1$s") found', $orderId );
			throw new \Aimeos\Client\JsonApi\Exception( $msg, 403 );
		}

		return \Aimeos\Controller\Frontend::create( $context, 'basket' )->load( $id, ['order/service'], false );
	}


	/**
	 * Returns the form helper object for building the payment form in the frontend
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface $orderItem Saved order item created for the basket object
	 * @param array $attributes Associative list of payment data pairs
	 * @return \Aimeos\MShop\Common\Helper\Form\Iface|null Form object with URL, parameters, etc. or null if no form data is required
	 */
	protected function getPaymentForm( \Aimeos\MShop\Order\Item\Iface $orderItem, array $attributes ) : ?\Aimeos\MShop\Common\Helper\Form\Iface
	{
		$view = $this->view();
		$context = $this->context();

		$total = $orderItem->getPrice()->getValue() + $orderItem->getPrice()->getCosts();
		$services = $orderItem->getService( \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT );

		if( $services === [] || $total <= '0.00' && $this->isSubscription( $orderItem->getProducts() ) === false )
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

		return null;
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
	 * @param \Aimeos\Map $products Ordered products implementing \Aimeos\MShop\Order\Item\Product\Iface
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
		 * to the templates directory (usually in templates/client/jsonapi).
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
