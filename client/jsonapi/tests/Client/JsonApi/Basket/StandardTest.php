<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Basket;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Basket\Standard( $this->context, 'basket' );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend\Basket\Factory::injectController( '\Aimeos\Controller\Frontend\Basket\Standard', null );
	}


	public function testDelete()
	{
		$body = '{"data": {"attributes": {"order.base.comment": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 'test', $result['data']['attributes']['order.base.comment'] );


		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertGreaterThan( 9, count( $result['data']['attributes'] ) );
		$this->assertEquals( '', $result['data']['attributes']['order.base.comment'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeletePluginException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$response = $this->object->get( $this->view->request(), $this->view->response() );
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
		$this->context->setUserId( $user->getId() );

		$params = ['id' => $this->getOrderBaseItem()->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertNotNull( $result['data']['id'] );
		$this->assertEquals( 12, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'This is another comment.', $result['data']['attributes']['order.base.comment'] );
		$this->assertEquals( 8, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetNoAccess()
	{
		$this->context->setUserId( null );

		$params = array(
			'id' => $this->getOrderBaseItem()->getId(),
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( '', $result['data']['attributes']['order.base.customerid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncluded()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $user->getId() );

		$params = array(
			'id' => $this->getOrderBaseItem()->getId(),
			'include' => 'basket/product,customer',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['relationships'] ) );
		$this->assertArrayHasKey( 'customer', $result['data']['relationships'] );
		$this->assertArrayHasKey( 'basket/product', $result['data']['relationships'] );
		$this->assertEquals( 2, count( $result['data']['relationships']['basket/product']['data'] ) );
		$this->assertEquals( 1, count( $result['data']['relationships']['customer']['data'] ) );
		$this->assertEquals( 3, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetFieldsIncluded()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $user->getId() );

		$params = array(
			'id' => $this->getOrderBaseItem()->getId(),
			'fields' => array(
				'basket' => 'order.base.comment',
				'basket/address' => 'order.base.address.firstname,order.base.address.lastname',
				'basket/product' => 'order.base.product.name,order.base.product.price',
				'basket/service' => 'order.base.service.name,order.base.service.price',
				'customer' => 'customer.id,customer.email'
			),
			'include' => 'basket/address,basket/product,basket/service,customer'
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
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
		$this->context->setUserId( $user->getId() );

		$params = array(
			'id' => $this->getOrderBaseItem()->getId(),
			'include' => '',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
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
		$object = $this->getObject( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$body = '{"data": {"attributes": {"order.base.comment": "test", "order.base.customerref": "abc"}}}	';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertGreaterThan( 10, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'test', $result['data']['attributes']['order.base.comment'] );
		$this->assertEquals( 'abc', $result['data']['attributes']['order.base.customerref'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchPluginException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$price = \Aimeos\MShop::create( $this->context, 'price' )->create();
		$locale = \Aimeos\MShop::create( $this->context, 'locale' )->create();

		$basket = $this->getMockBuilder( \Aimeos\MShop\Order\Item\Base\Standard::class )
			->setConstructorArgs( [$price, $locale] )
			->setMethods( ['check'] )
			->getMock();

		$basket->expects( $this->once() )->method( 'check' )->will( $this->returnSelf() );

		$object = $this->getObject( ['get', 'store'], $this->returnValue( $basket ) );


		$body = '{"data": {"attributes": {"order.base.comment": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
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
		$object = $this->getObject( 'get', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->getObject( 'get', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->getObject( 'get', $this->throwException( new \Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
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
		$this->assertEquals( 2, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a stored basket
	 *
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Basket object
	 */
	protected function getOrderBaseItem()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order/base' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'order.base.price', '672.00' ) );

		if( ( $item = $manager->search( $search, ['order/base/product'] )->first() ) === null ) {
			throw new \Exception( 'No order/base item with price "672.00" found' );
		}

		return $item;
	}


	/**
	 * Returns a test object with a mocked basket controller
	 *
	 * @param string $method Basket controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function getObject( $method, $result )
	{
		$methods = (array) $method;

		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Basket\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( $methods )
			->getMock();

		foreach( $methods as $method ) {
			$cntl->expects( $this->once() )->method( $method )->will( $result );
		}

		\Aimeos\Controller\Frontend\Basket\Factory::injectController( '\Aimeos\Controller\Frontend\Basket\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Basket\Standard( $this->context, 'basket' );
		$object->setView( $this->view );

		return $object;
	}
}
