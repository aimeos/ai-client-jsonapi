<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
 */


namespace Aimeos\Client\JsonApi\Order;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		\Aimeos\Controller\Frontend::cache( true );

		$this->context = \TestHelper::context();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Order\Standard( $this->context );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testGet()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $user->getId() );

		$params = array( 'fields' => array( 'order' => 'order.id,order.channel' ) );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
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
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $customer->getId() );

		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$search = $manager->filter()->slice( 0, 1 );
		$search->setConditions( $search->compare( '==', 'order.channel', 'phone' ) );

		if( ( $item = $manager->search( $search )->first() ) === null ) {
			throw new \RuntimeException( 'No order item found' );
		}

		$params = [
			'id' => $item->getId(),
			'include' => 'order,order.product,order.service,order.address,order.coupon,customer',
			'fields' => ['customer' => 'customer.id,customer.email']
		];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'order', $result['data']['type'] );
		$this->assertEquals( 19, count( $result['data']['attributes'] ) );
		$this->assertEquals( 5, count( $result['data']['relationships'] ) );
		$this->assertEquals( 9, count( $result['included'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$object = $this->object( 'parse', $this->throwException( new \Aimeos\Controller\Frontend\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->object( 'parse', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->object( 'parse', $this->throwException( new \Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$order = \Aimeos\MShop::create( $this->context, 'order' )->create();

		$form = new \Aimeos\MShop\Common\Helper\Form\Standard();
		$templatePaths = \TestHelper::getTemplatePaths();

		$manager = $this->getMockBuilder( \Aimeos\MShop\Order\Manager\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['save'] )
			->getMock();

		$manager->expects( $this->once() )->method( 'save' )->will( $this->returnArgument( 0 ) );

		\Aimeos\MShop::inject( \Aimeos\MShop\Order\Manager\Standard::class, $manager );

		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Order\Standard::class )
			->setConstructorArgs( [$this->context, 'order'] )
			->onlyMethods( ['getOrder', 'getPaymentForm'] )
			->getMock();

		$object->setView( $this->view );

		$object->expects( $this->once() )->method( 'getOrder' )->will( $this->returnValue( $order ) );
		$object->expects( $this->once() )->method( 'getPaymentForm' )->will( $this->returnValue( $form ) );

		$body = '{"data": {"attributes": {"order.id": -1}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'order', $result['data']['type'] );
		$this->assertEquals( 19, count( $result['data']['attributes'] ) );
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


	public function testPostNoId()
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


	public function testGetOrder()
	{
		$orderId = $this->getOrderItem()->getId();
		$this->context->session()->set( 'aimeos/order.id', $orderId );

		$result = $this->access( 'getOrder' )->invokeArgs( $this->object, [$orderId] );
		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
	}


	public function testGetOrderException()
	{
		$orderId = $this->getOrderItem()->getId();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		$this->access( 'getOrder' )->invokeArgs( $this->object, [$orderId] );
	}


	public function testGetPaymentForm()
	{
		$order = $this->getOrderItem();

		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Service\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['process'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'process' )
			->will( $this->returnValue( new \Aimeos\MShop\Common\Helper\Form\Standard() ) );

		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Service\Standard', $cntl );
		$result = $this->access( 'getPaymentForm' )->invokeArgs( $this->object, [$order, []] );
		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Service\Standard', null );

		$this->assertInstanceOf( \Aimeos\MShop\Common\Helper\Form\Iface::class, $result );
	}


	public function testGetPaymentFormNoPayment()
	{
		$order = \Aimeos\MShop::create( $this->context, 'order' )->create();

		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Order\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['save'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'save' );

		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Order\Standard', $cntl );
		$result = $this->access( 'getPaymentForm' )->invokeArgs( $this->object, [$order, []] );
		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Order\Standard', null );

		$this->assertInstanceOf( \Aimeos\MShop\Common\Helper\Form\Iface::class, $result );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Client\JsonApi\Order\Standard::class );
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
	protected function object( $method, $result )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Order\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Order\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Order\Standard( $this->context, 'order' );
		$object->setView( $this->view );


		return $object;
	}


	/**
	 * Returns a stored order
	 *
	 * @return \Aimeos\MShop\Order\Item\Iface Order object
	 */
	protected function getOrderItem()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$search = $manager->filter()->add( 'order.price', '==', '672.00' );
		$ref = ['order/address', 'order/coupon', 'order/product', 'order/service'];

		return $manager->search( $search, $ref )->first( new \Exception( 'No order item found' ) );
	}
}
