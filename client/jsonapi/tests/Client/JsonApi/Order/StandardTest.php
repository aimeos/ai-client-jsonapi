<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi\Order;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->object = new \Aimeos\Client\JsonApi\Order\Standard( $this->context, 'order' );
		$this->object->setView( $this->view );
	}


	protected function tearDown()
	{
		\Aimeos\Controller\Frontend\Order\Factory::injectController( '\Aimeos\Controller\Frontend\Order\Standard', null );
		unset( $this->context, $this->object, $this->view );
	}


	public function testGet()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001' );
		$this->context->setUserId( $user->getId() );

		$params = array( 'fields' => array( 'order' => 'order.id,order.type' ) );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 4, $result['meta']['total'] );
		$this->assertEquals( 'order', $result['data'][0]['type'] );
		$this->assertEquals( 2, count( $result['data'][0]['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$this->context->setEditor( 'core:unittest' );

		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'order' );
		$search = $manager->createSearch()->setSlice( 0, 1 );
		$search->setConditions( $search->compare( '==', 'order.type', 'phone' ) );
		$items = $manager->searchItems( $search );

		if( ( $item = reset( $items ) ) === false ) {
			throw new \RuntimeException( 'No order item found' );
		}

		$params = array( 'id' => $item->getId() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'order', $result['data']['type'] );
		$this->assertGreaterThan( 7, count( $result['data']['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$object = $this->getObject( 'createFilter', $this->throwException( new \Aimeos\Controller\Frontend\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getObject( 'createFilter', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getObject( 'createFilter', $this->throwException( new \Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$basket = \Aimeos\MShop\Factory::createManager( $this->context, 'order/base' )->createItem();
		$order = \Aimeos\MShop\Factory::createManager( $this->context, 'order' )->createItem();
		$form = new \Aimeos\MShop\Common\Item\Helper\Form\Standard();
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Order\Standard' )
			->setConstructorArgs( [$this->context, 'order'] )
			->setMethods( ['createOrder', 'getBasket', 'getPaymentForm'] )
			->getMock();

		$object->setView( $this->view );

		$object->expects( $this->once() )->method( 'getBasket' )->will( $this->returnValue( $basket ) );
		$object->expects( $this->once() )->method( 'createOrder' )->will( $this->returnValue( $order ) );
		$object->expects( $this->once() )->method( 'getPaymentForm' )->will( $this->returnValue( $form ) );

		$body = '{"data": {"attributes": {"order.baseid": -1}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'order', $result['data']['type'] );
		$this->assertEquals( 8, count( $result['data']['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostNoData()
	{
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( '{"data": []}' ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 400, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostNoBaseId()
	{
		$body = '{"data": {"attributes": {}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 400, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostClientException()
	{
		$request = $this->getMockBuilder( 'Psr\Http\Message\ServerRequestInterface' )->getMock();

		$request->expects( $this->once() )->method( 'getBody' )
			->will( $this->throwException( new \Aimeos\Client\JsonApi\Exception( '', 400 ) ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 400, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostControllerException()
	{
		$request = $this->getMockBuilder( 'Psr\Http\Message\ServerRequestInterface' )->getMock();

		$request->expects( $this->once() )->method( 'getBody' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Exception() ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$request = $this->getMockBuilder( 'Psr\Http\Message\ServerRequestInterface' )->getMock();

		$request->expects( $this->once() )->method( 'getBody' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$request = $this->getMockBuilder( 'Psr\Http\Message\ServerRequestInterface' )->getMock();

		$request->expects( $this->once() )->method( 'getBody' )
			->will( $this->throwException( new \Exception() ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testOptions()
	{
		$response = $this->object->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( null, $result['meta']['prefix'] );
		$this->assertEquals( 1, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testCreateOrder()
	{
		$order = \Aimeos\MShop\Factory::createManager( $this->context, 'order' )->createItem();

		$cntl = $this->getMockBuilder( '\Aimeos\Controller\Frontend\Order\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['addItem', 'block'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'addItem' )->will( $this->returnValue( $order ) );
		$cntl->expects( $this->once() )->method( 'block' );

		\Aimeos\Controller\Frontend\Order\Factory::injectController( '\Aimeos\Controller\Frontend\Order\Standard', $cntl );
		$result = $this->access( 'createOrder' )->invokeArgs( $this->object, [-1] );
		\Aimeos\Controller\Frontend\Order\Factory::injectController( '\Aimeos\Controller\Frontend\Order\Standard', null );

		$this->assertInstanceOf( '\Aimeos\MShop\Order\Item\Iface', $result );
	}


	public function testGetBasket()
	{
		$basketId = $this->getOrderBaseItem()->getId();
		$this->context->getSession()->set( 'aimeos/order.baseid', $basketId );

		$result = $this->access( 'getBasket' )->invokeArgs( $this->object, [$basketId] );
		$this->assertInstanceOf( '\Aimeos\MShop\Order\Item\Base\Iface', $result );
	}


	public function testGetBasketException()
	{
		$basketId = $this->getOrderBaseItem()->getId();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		$this->access( 'getBasket' )->invokeArgs( $this->object, [$basketId] );
	}


	public function testGetPaymentForm()
	{
		$basket = $this->getOrderBaseItem();
		$order = \Aimeos\MShop\Factory::createManager( $this->context, 'order' )->createItem();

		$cntl = $this->getMockBuilder( '\Aimeos\Controller\Frontend\Service\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['process'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'process' )
			->will( $this->returnValue( new \Aimeos\MShop\Common\Item\Helper\Form\Standard() ) );

		\Aimeos\Controller\Frontend\Service\Factory::injectController( '\Aimeos\Controller\Frontend\Service\Standard', $cntl );
		$result = $this->access( 'getPaymentForm' )->invokeArgs( $this->object, [$basket, $order, []] );
		\Aimeos\Controller\Frontend\Service\Factory::injectController( '\Aimeos\Controller\Frontend\Service\Standard', null );

		$this->assertInstanceOf( '\Aimeos\MShop\Common\Item\Helper\Form\Iface', $result );
	}


	public function testGetPaymentFormNoPayment()
	{
		$basket = \Aimeos\MShop\Factory::createManager( $this->context, 'order/base' )->createItem();
		$order = \Aimeos\MShop\Factory::createManager( $this->context, 'order' )->createItem();

		$cntl = $this->getMockBuilder( '\Aimeos\Controller\Frontend\Order\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['saveItem'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'saveItem' );

		\Aimeos\Controller\Frontend\Order\Factory::injectController( '\Aimeos\Controller\Frontend\Order\Standard', $cntl );
		$result = $this->access( 'getPaymentForm' )->invokeArgs( $this->object, [$basket, $order, []] );
		\Aimeos\Controller\Frontend\Order\Factory::injectController( '\Aimeos\Controller\Frontend\Order\Standard', null );

		$this->assertInstanceOf( '\Aimeos\MShop\Common\Item\Helper\Form\Iface', $result );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( '\Aimeos\Client\JsonApi\Order\Standard' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}


	/**
	 * Returns a test object with a mocked order controller
	 *
	 * @param string $method Order controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function getObject( $method, $result )
	{
		$cntl = $this->getMockBuilder( '\Aimeos\Controller\Frontend\Order\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend\Order\Factory::injectController( '\Aimeos\Controller\Frontend\Order\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Order\Standard( $this->context, 'order' );
		$object->setView( $this->view );


		return $object;
	}


	/**
	 * Returns a stored basket
	 *
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Basket object
	 */
	protected function getOrderBaseItem()
	{
		$baseManager = \Aimeos\MShop\Factory::createManager( $this->context, 'order/base' );

		$search = $baseManager->createSearch();
		$search->setConditions( $search->compare( '==', 'order.base.price', '672.00') );

		$items = $baseManager->searchItems( $search );

		if( ( $item = reset( $items ) ) === false ) {
			throw new \Exception( 'No order/base item with price "672.00" found' );
		}

		return $baseManager->load( $item->getId() );
	}
}