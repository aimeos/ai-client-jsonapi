<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2024
 */


namespace Aimeos\Client\JsonApi\Basket;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $view;


	protected function setUp() : void
	{
		\Aimeos\Controller\Frontend::cache( true );

		$this->context = \TestHelper::context();
		$this->view = $this->context->view();
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->context );
	}


	public function testDelete()
	{
		$body = '{"data": {"attributes": {"order.comment": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 'test', $result['data']['attributes']['order.comment'] );


		$response = $this->object()->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertGreaterThan( 9, count( $result['data']['attributes'] ) );
		$this->assertEquals( '', $result['data']['attributes']['order.comment'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeletePluginException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertGreaterThan( 9, count( $result['data']['attributes'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUser( $user );

		$params = ['id' => $this->getOrderItem()->getId()];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertNotNull( $result['data']['id'] );
		$this->assertEquals( 19, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'This is another comment.', $result['data']['attributes']['order.comment'] );
		$this->assertEquals( 8, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetNoAccess()
	{
		$this->context->setUser( null );

		$params = array(
			'id' => $this->getOrderItem()->getId(),
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( '', $result['data']['attributes']['order.customerid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncluded()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUser( $user );

		$params = array(
			'id' => $this->getOrderItem()->getId(),
			'include' => 'basket.product,customer',
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['relationships'] ) );
		$this->assertArrayHasKey( 'customer', $result['data']['relationships'] );
		$this->assertArrayHasKey( 'basket.product', $result['data']['relationships'] );
		$this->assertEquals( 2, count( $result['data']['relationships']['basket.product']['data'] ) );
		$this->assertEquals( 1, count( $result['data']['relationships']['customer']['data'] ) );
		$this->assertEquals( 3, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetFieldsIncluded()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUser( $user );

		$params = array(
			'id' => $this->getOrderItem()->getId(),
			'fields' => array(
				'basket' => 'order.comment',
				'basket.address' => 'order.address.firstname,order.address.lastname',
				'basket.product' => 'order.product.name,order.product.price',
				'basket.service' => 'order.service.name,order.service.price',
				'customer' => 'customer.id,customer.email'
			),
			'include' => 'basket.address,basket.product,basket.service,customer'
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['attributes'] ) );
		$this->assertEquals( 7, count( $result['included'] ) );

		foreach( $result['included'] as $entry ) {
			$this->assertCount( 2, $entry['attributes'] );
		}

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncludedNone()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUser( $user );

		$params = array(
			'id' => $this->getOrderItem()->getId(),
			'include' => '',
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 0, count( $result['data']['relationships'] ) );
		$this->assertEquals( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$body = '{"data": {"attributes": {"order.comment": "test", "order.customerref": "abc"}}}	';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertGreaterThan( 10, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'test', $result['data']['attributes']['order.comment'] );
		$this->assertEquals( 'abc', $result['data']['attributes']['order.customerref'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchPluginException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$this->controller( 'setType' )->expects( $this->once() )->method( 'setType' )
			->will( $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$price = \Aimeos\MShop::create( $this->context, 'price' )->create();
		$locale = \Aimeos\MShop::create( $this->context, 'locale' )->create();

		$basket = $this->getMockBuilder( \Aimeos\MShop\Order\Item\Standard::class )
			->setConstructorArgs( ['order.', ['.price' => $price, '.locale' => $locale]] )
			->onlyMethods( ['check'] )
			->getMock();

		$basket->expects( $this->once() )->method( 'check' )->willReturnSelf();

		$cntl = $this->controller( ['get', 'store'] );
		$cntl->expects( $this->once() )->method( 'get' )->willReturn( $basket );
		$cntl->expects( $this->once() )->method( 'store' )->willReturn( $basket );


		$body = '{"data": {"attributes": {"order.comment": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertNotNull( $result['data']['id'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertGreaterThan( 9, count( $result['data']['attributes'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostPluginException()
	{
		$this->controller( 'get' )->expects( $this->once() )->method( 'get' )
			->will( $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $this->object()->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$this->controller( 'get' )->expects( $this->once() )->method( 'get' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object()->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$this->controller( 'get' )->expects( $this->once() )->method( 'get' )
			->will( $this->throwException( new \Exception() ) );

		$response = $this->object()->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testOptions()
	{
		$response = $this->object()->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( null, $result['meta']['prefix'] );
		$this->assertEquals( 2, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a stored order
	 *
	 * @return \Aimeos\MShop\Order\Item\Iface Order object
	 */
	protected function getOrderItem()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'order.price', '672.00' ) );

		if( ( $item = $manager->search( $search, ['order/product'] )->first() ) === null ) {
			throw new \Exception( 'No order item with price "672.00" found' );
		}

		return $item;
	}


	/**
	 * Returns a mocked basket controller
	 *
	 * @param array|string $methods Basket controller method name to mock
	 * @return \Aimeos\Controller\Frontend\Basket\Standard Mocked basket controller
	 */
	protected function controller( $methods )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Basket\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( (array) $methods )
			->getMock();

		\Aimeos\Controller\Frontend::inject( \Aimeos\Controller\Frontend\Basket\Standard::class, $cntl );

		return $cntl;
	}


	/**
	 * Returns the JSON API client object
	 *
	 * @return \Aimeos\Client\JsonApi\Basket\Standard JSON API client object
	 */
	protected function object()
	{
		$object = new \Aimeos\Client\JsonApi\Basket\Standard( $this->context );
		$object->setView( $this->view );

		return $object;
	}
}
