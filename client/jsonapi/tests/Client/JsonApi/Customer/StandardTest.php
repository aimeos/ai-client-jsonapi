<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Customer;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		\Aimeos\Controller\Frontend::cache( true );

		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Customer\Standard( $this->context, 'customer' );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testDelete()
	{
		$id = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' )->getId();
		$this->context->setUserId( $id );

		$this->getObject( 'delete', $this->returnSelf() );

		$response = $this->object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteControllerException()
	{
		$object = $this->getObject( 'delete', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->getObject( 'delete', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->getObject( 'delete', $this->throwException( new \Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $user->getId() );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
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


	public function testGetNoAccess()
	{
		$this->context->setUserId( -1 );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetIncluded()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $user->getId() );

		$params = ['include' => 'customer/address,customer/property'];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['customer/address']['data'] ) );
		$this->assertEquals( 1, count( $result['data']['relationships']['customer/property']['data'] ) );
		$this->assertEquals( 2, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncludedNone()
	{
		$user = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $user->getId() );

		$params = ['include' => ''];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
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
		$object = $this->getObject( 'get', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getObject( 'get', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getObject( 'get', $this->throwException( new \Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$id = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' )->getId();
		$this->context->setUserId( $id );

		$this->getObject( 'store', $this->returnSelf() );

		$body = '{"data": {"attributes": {"customer.status": 0,"customer.latitude": 50.1}}}	';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->patch( $request, $this->view->response() );
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
		$object = $this->getObject( 'store', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->getObject( 'store', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->getObject( 'store', $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$body = '{"data": {"attributes": {"customer.code": "unittest-japi"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$object = $this->getObject( 'store', $this->returnSelf() );
		$response = $object->post( $request, $this->view->response() );
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
		$object = $this->getObject( 'store', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": {}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->getObject( 'store', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": {}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$response = $this->object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 400, $response->getStatusCode() );
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
		$this->assertEquals( 25, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a test object with a mocked customer controller
	 *
	 * @param string $method Customer controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function getObject( $method, $result )
	{
		$id = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' )->getId();
		$this->context->setUserId( $id );

		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Customer\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend::inject( 'customer', $cntl );

		$object = new \Aimeos\Client\JsonApi\Customer\Standard( $this->context, 'customer' );
		$object->setView( $this->view );

		return $object;
	}
}
