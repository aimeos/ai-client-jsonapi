<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Customer\Address;


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

		$this->object = new \Aimeos\Client\JsonApi\Customer\Address\Standard( $this->context, 'customer/address' );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testDelete()
	{
		$custManager = \Aimeos\MShop::create( $this->context, 'customer' );
		$customer = $custManager->find( 'test@example.com', ['customer/address'] )->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer->setId( null ) );
		$this->context->setUserId( $customer->getId() );


		$params = ['id' => $customer->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "customer/address", "id": ' . $customer->getAddressItems()->first()->getId() . '}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteById()
	{
		$custManager = \Aimeos\MShop::create( $this->context, 'customer' );
		$customer = $custManager->find( 'test@example.com', ['customer/address'] )->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer->setId( null ) );
		$this->context->setUserId( $customer->getId() );


		$params = ['id' => $customer->getId(), 'relatedid' => $customer->getAddressItems()->first()->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteControllerException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );
		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );
		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Exception() ) );
		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUserId( $customer->getId() );

		$params = ['id' => $customer->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/address', $result['data'][0]['type'] );
		$this->assertNotNull( $result['data'][0]['id'] );
		$this->assertGreaterThan( 21, count( $result['data'][0]['attributes'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com', ['customer/address'] );
		$id = $customer->getAddressItems()->first()->getId();
		$this->context->setUserId( $customer->getId() );

		$params = array(
			'id' => $customer->getId(),
			'related' => 'address',
			'relatedid' => $id,
			'fields' => ['customer/address' => 'customer.address.id,customer.address.firstname'],
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/address', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['attributes'] ) );
		$this->assertNotNull( $result['data']['id'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );
		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );
		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Exception() ) );
		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$custManager = \Aimeos\MShop::create( $this->context, 'customer' );
		$customer = $custManager->find( 'test@example.com', ['customer/address'] )->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer->setId( null ) );
		$this->context->setUserId( $customer->getId() );


		$params = ['id' => $customer->getId(), 'relatedid' => $customer->getAddressItems()->first()->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"attributes": {"customer.address.lastname": "test"}}}	';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/address', $result['data']['type'] );
		$this->assertGreaterThan( 21, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'test', $result['data']['attributes']['customer.address.lastname'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchControllerException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$custManager = \Aimeos\MShop::create( $this->context, 'customer' );
		$customer = $custManager->create()->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer );
		$this->context->setUserId( $customer->getId() );


		$params = ['id' => $customer->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "customer/address", "attributes": {"customer.address.firstname": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/address', $result['data'][0]['type'] );
		$this->assertEquals( 'test', $result['data'][0]['attributes']['customer.address.firstname'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMultiple()
	{
		$custManager = \Aimeos\MShop::create( $this->context, 'customer' );
		$customer = $custManager->create()->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer );
		$this->context->setUserId( $customer->getId() );


		$params = ['id' => $customer->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": [
			{"type": "customer/address", "attributes": {"customer.address.firstname": "test"}},
			{"type": "customer/address", "attributes": {"customer.address.lastname": "test"}}
		]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertNotNull( $result['data'][1]['id'] );
		$this->assertEquals( 'customer/address', $result['data'][1]['type'] );
		$this->assertEquals( 'test', $result['data'][0]['attributes']['customer.address.firstname'] );
		$this->assertEquals( 'test', $result['data'][1]['attributes']['customer.address.lastname'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostControllerException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->getObject( 'uses', $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
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
		$this->assertEquals( 21, count( $result['meta']['attributes'] ) );
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
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Customer\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend::inject( 'customer', $cntl );

		$object = new \Aimeos\Client\JsonApi\Customer\Address\Standard( $this->context, 'customer/address' );
		$object->setView( $this->view );

		return $object;
	}
}
