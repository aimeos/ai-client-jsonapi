<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi\Customer;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->object = new \Aimeos\Client\JsonApi\Customer\Standard( $this->context, 'customer' );
		$this->object->setView( $this->view );
	}


	protected function tearDown()
	{
		\Aimeos\Controller\Frontend\Customer\Factory::injectController( '\Aimeos\Controller\Frontend\Customer\Standard', null );
		unset( $this->context, $this->object, $this->view );
	}


	public function testDelete()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$item = $manager->createItem()->setCode( 'unittest-japi' );
		$item = $manager->saveItem( $item );

		$this->context->setUserId( $item->getId() );

		$params = array( 'id' => $item->getId() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );

		$this->setExpectedException( '\Aimeos\MShop\Exception' );
		$manager->findItem( 'unittest-japi' );
	}


	public function testDeleteControllerException()
	{
		$object = $this->getObject( 'deleteItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->getObject( 'deleteItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->getObject( 'deleteItem', $this->throwException( new \Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001' );
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


	public function testGetById()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001' );
		$this->context->setUserId( $user->getId() );

		$params = array(
			'id' => $user->getId(),
			'fields' => array( 'customer' => 'customer.id,customer.code' ),
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertNotNull( $result['data']['id'] );
		$this->assertGreaterThan( 1, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'UTC001', $result['data']['attributes']['customer.code'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetNoAccess()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001' );
		$this->context->setUserId( null );

		$params = array( 'id' => $user->getId() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetIncluded()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001' );
		$this->context->setUserId( $user->getId() );

		$params = array(
			'id' => $user->getId(),
			'include' => 'customer/address',
		);
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
		$this->assertEquals( 1, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncludedNone()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001' );
		$this->context->setUserId( $user->getId() );

		$params = array(
			'id' => $user->getId(),
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
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertArrayNotHasKey( 'relationships', $result['data'] );
		$this->assertEquals( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$object = $this->getObject( 'getItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getObject( 'getItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getObject( 'getItem', $this->throwException( new \Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$item = $manager->createItem()->setCode( 'unittest-japi' )->setStatus( 1 );
		$item = $manager->saveItem( $item );

		$this->context->setUserId( $item->getId() );

		$params = array( 'id' => $item->getId() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"attributes": {"customer.status": 0}}}	';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$manager->deleteItem( $item->getId() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertGreaterThan( 24, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'unittest-japi', $result['data']['attributes']['customer.code'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchControllerException()
	{
		$object = $this->getObject( 'editItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->getObject( 'editItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->getObject( 'editItem', $this->throwException( new \Exception() ) );

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


		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertNotNull( $result['data']['id'] );
		$this->assertEquals( 'customer', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['attributes'] ) ); // only "customer.id" for POST

		$this->assertArrayNotHasKey( 'errors', $result );


		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$manager->deleteItem( $result['data']['id'] );
	}


	public function testPostControllerException()
	{
		$object = $this->getObject( 'addItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": {}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->getObject( 'addItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

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
		$cntl = $this->getMockBuilder( '\Aimeos\Controller\Frontend\Customer\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend\Customer\Factory::injectController( '\Aimeos\Controller\Frontend\Customer\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Customer\Standard( $this->context, 'customer' );
		$object->setView( $this->view );

		return $object;
	}
}