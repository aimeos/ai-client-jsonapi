<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
 */


namespace Aimeos\Client\JsonApi\Basket\Product;


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

		$this->object = new \Aimeos\Client\JsonApi\Basket\Product\Standard( $this->context );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testDelete()
	{
		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNC' )->getId();
		$body = '{"data": {"type": "basket.product", "attributes": {"product.id": ' . $prodId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$this->object->post( $request, $this->view->response() );

		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNE' )->getId();
		$body = '{"data": {"type": "basket.product", "attributes": {"product.id": ' . $prodId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 2, count( $result['data']['relationships']['basket.product']['data'] ) );


		$body = '{"data": {"type": "basket.product", "id": 0}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket.product']['data'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteById()
	{
		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNC' )->getId();
		$body = '{"data": {"type": "basket.product", "attributes": {"product.id": ' . $prodId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$this->object->post( $request, $this->view->response() );

		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNE' )->getId();
		$body = '{"data": {"type": "basket.product", "attributes": {"product.id": ' . $prodId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 2, count( $result['data']['relationships']['basket.product']['data'] ) );


		$params = array( 'id' => 'default', 'relatedid' => 0 );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket.product']['data'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeletePluginException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNC' )->getId();
		$body = '{"data": {"type": "basket.product", "attributes": {"product.id": ' . $prodId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 1, count( $result['data']['relationships']['basket.product']['data'] ) );


		$body = '{"data": {"type": "basket.product", "id": 0, "attributes": {"quantity": 2}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertArrayHasKey( 'basket.product', $result['data']['relationships'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket.product']['data'] ) );
		$this->assertEquals( 2, $result['included'][0]['attributes']['order.product.quantity'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchPluginException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNC' )->getId();
		$body = '{"data": {"type": "basket.product", "attributes": {"product.id": ' . $prodId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket.product']['data'] ) );
		$this->assertEquals( $prodId, $result['included'][0]['attributes']['order.product.productid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMultiple()
	{
		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNC' )->getId();
		$prodId2 = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNE' )->getId();

		$body = '{"data": [{
			"type": "basket.product", "attributes": {"product.id": ' . $prodId . '}
		}, {
			"type": "basket.product", "attributes": {"product.id": ' . $prodId2 . '}
		}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['relationships']['basket.product']['data'] ) );
		$this->assertEquals( $prodId, $result['included'][0]['attributes']['order.product.productid'] );
		$this->assertEquals( $prodId2, $result['included'][1]['attributes']['order.product.productid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostPluginException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Exception() ) );

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
		$this->assertEquals( 8, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a stored product item from the order
	 *
	 * @return \Aimeos\MShop\Order\Item\Product\Iface Ordered product item
	 */
	protected function getOrderProductItem()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order/product' );
		$search = $manager->filter()->slice( 0, 1 );

		if( ( $item = $manager->search( $search )->first() ) === null ) {
			throw new \Exception( 'No order/product item found' );
		}

		return $item;
	}



	/**
	 * Returns a test object with a mocked basket controller
	 *
	 * @param string $method Basket controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function object( $method, $result )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Basket\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Basket\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Basket\Product\Standard( $this->context );
		$object->setView( $this->view );

		return $object;
	}
}
