<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2024
 */


namespace Aimeos\Client\JsonApi\Customer;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $view;


	protected function setUp() : void
	{
		\Aimeos\Controller\Frontend::cache( true );

		$this->context = \TestHelper::context();
		$this->view = $this->context->view();

		$this->context->setUser( \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' ) );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->context );
	}


	public function testDelete()
	{
		$this->controller( 'delete' )->expects( $this->once() )->method( 'delete' )->willReturnSelf();

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteControllerException()
	{
		$this->controller( 'delete' )->expects( $this->once() )->method( 'delete' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$this->controller( 'delete' )->expects( $this->once() )->method( 'delete' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$this->controller( 'delete' )->expects( $this->once() )->method( 'delete' )
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
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertGreaterThan( 13, count( $result['data']['attributes'] ) );
		$this->assertEquals( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncluded()
	{
		$params = ['include' => 'customer.address,customer.property'];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['customer.address']['data'] ) );
		$this->assertEquals( 1, count( $result['data']['relationships']['customer.property']['data'] ) );
		$this->assertEquals( 2, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncludedNone()
	{
		$params = ['include' => ''];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertArrayNotHasKey( 'relationships', $result['data'] );
		$this->assertEquals( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$this->controller( 'get' )->expects( $this->once() )->method( 'get' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$this->controller( 'get' )->expects( $this->once() )->method( 'get' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$this->controller( 'get' )->expects( $this->once() )->method( 'get' )
			->will( $this->throwException( new \Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$this->controller( 'store' )->expects( $this->once() )->method( 'store' )->willReturnSelf();

		$body = '{"data": {"attributes": {"customer.status": 0,"customer.latitude": 50.1}}}	';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object()->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertGreaterThanOrEqual( 24, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'test@example.com', $result['data']['attributes']['customer.code'] );
		$this->assertEquals( '50.1', $result['data']['attributes']['customer.latitude'] );

		$this->assertArrayNotHasKey( 'customer.status', $result['data']['attributes'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchControllerException()
	{
		$this->controller( 'store' )->expects( $this->once() )->method( 'store' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$this->controller( 'store' )->expects( $this->once() )->method( 'store' )
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
		$this->controller( 'store' )->expects( $this->once() )->method( 'store' )
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
		$body = '{"data": {"attributes": {"customer.code": "unittest-japi"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$this->controller( 'store' )->expects( $this->once() )->method( 'store' )->willReturnSelf();

		$response = $this->object()->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertNotNull( $result['data']['id'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['attributes'] ) ); // only "customer.id" for POST

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostControllerException()
	{
		$this->controller( 'store' )->expects( $this->once() )->method( 'store' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": {}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$this->controller( 'store' )->expects( $this->once() )->method( 'store' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": {}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object()->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$response = $this->object()->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 400, $response->getStatusCode() );
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
		$this->assertEquals( 25, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a mocked customer controller
	 *
	 * @param array|string $methods Customer controller method name to mock
	 * @return \Aimeos\Controller\Frontend\Customer\Standard Mocked customer controller
	 */
	protected function controller( $methods )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Customer\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( (array) $methods )
			->getMock();

		\Aimeos\Controller\Frontend::inject( \Aimeos\Controller\Frontend\Customer\Standard::class, $cntl );

		return $cntl;
	}


	/**
	 * Returns the JSON API client object
	 *
	 * @return \Aimeos\Client\JsonApi\Customer\Standard JSON API client object
	 */
	protected function object()
	{
		$object = new \Aimeos\Client\JsonApi\Customer\Standard( $this->context );
		$object->setView( $this->view );

		return $object;
	}
}
