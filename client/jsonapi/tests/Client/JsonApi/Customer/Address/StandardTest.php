<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi\Customer\Address;


class StandardTest extends \PHPUnit_Framework_TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getTemplatePaths();
		$this->view = $this->context->getView();

		$this->object = new \Aimeos\Client\JsonApi\Customer\Address\Standard( $this->context, $this->view, $templatePaths, 'customer/address' );
	}


	protected function tearDown()
	{
		\Aimeos\Controller\Frontend\Customer\Factory::injectController( '\Aimeos\Controller\Frontend\Customer\Standard', null );
	}


	public function testDelete()
	{
		$custManager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/address' );

		$userId = $custManager->findItem( 'UTC001' )->getId();
		$this->context->setUserId( $userId );
		$item = $manager->createItem()->setParentId( $userId );
		$manager->saveItem( $item );


		$params = array( 'id' => $userId );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "customer/address", "id": ' . $item->getId() . '}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteById()
	{
		$custManager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/address' );

		$userId = $custManager->findItem( 'UTC001' )->getId();
		$this->context->setUserId( $userId );
		$item = $manager->createItem()->setParentId( $userId );
		$manager->saveItem( $item );


		$params = array( 'id' => $userId, 'relatedid' => $item->getId() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


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
		$object = $this->getObject( 'deleteAddressItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->getObject( 'deleteAddressItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->getObject( 'deleteAddressItem', $this->throwException( new \Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001' );
		$this->context->setUserId( $user->getId() );

		$params = array( 'id' => $user->getId() );
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
		$this->assertGreaterThan( 22, count( $result['data'][0]['attributes'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001', ['customer/address'] );
		$this->context->setUserId( $user->getId() );
		$addrItems = $user->getAddressItems();

		if( ( $addrItem = reset( $addrItems ) ) === false ) {
			throw new \RuntimeException( 'No address item found for "UTC001"' );
		}

		$params = array(
			'id' => $user->getId(),
			'related' => 'address',
			'relatedid' => $addrItem->getId(),
			'fields' => array( 'customer/address' => 'customer.address.id,customer.address.firstname' ),
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
		$object = $this->getObject( 'getAddressItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getObject( 'getAddressItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getObject( 'getAddressItem', $this->throwException( new \Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$custManager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/address' );

		$customerId = $custManager->findItem( 'UTC001' )->getId();
		$item = $manager->createItem()->setParentId( $customerId );
		$manager->saveItem( $item );

		$this->context->setUserId( $customerId );

		$params = array( 'id' => $item->getId(), 'relatedid' => $item->getId() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"attributes": {"customer.address.lastname": "test"}}}	';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$manager->deleteItem( $item->getId() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/address', $result['data']['type'] );
		$this->assertGreaterThan( 22, count( $result['data']['attributes'] ) );
		$this->assertEquals( 'test', $result['data']['attributes']['customer.address.lastname'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchControllerException()
	{
		$object = $this->getObject( 'editAddressItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->getObject( 'editAddressItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->getObject( 'editAddressItem', $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$userId = $manager->findItem( 'UTC001' )->getId();
		$this->context->setUserId( $userId );

		$params = array( 'id' => $userId );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "customer/address", "attributes": {"customer.address.firstname": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/address', $result['data'][0]['type'] );
		$this->assertEquals( 'test', $result['data'][0]['attributes']['customer.address.firstname'] );

		$this->assertArrayNotHasKey( 'errors', $result );


		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/address' );
		$manager->deleteItem( $result['data'][0]['id'] );
	}


	public function testPostMultiple()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$userId = $manager->findItem( 'UTC001' )->getId();
		$this->context->setUserId( $userId );

		$params = array( 'id' => $userId );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": [{
			"type": "customer/address", "attributes": {"customer.address.firstname": "test"}
		}, {
			"type": "customer/address", "attributes": {"customer.address.lastname": "test"}
		}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertNotNull( $result['data'][0]['id'] );
		$this->assertEquals( 'customer/address', $result['data'][0]['type'] );
		$this->assertEquals( 'test', $result['data'][0]['attributes']['customer.address.firstname'] );
		$this->assertEquals( 'test', $result['data'][1]['attributes']['customer.address.lastname'] );

		$this->assertArrayNotHasKey( 'errors', $result );


		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/address' );
		$manager->deleteItems( [$result['data'][0]['id'], $result['data'][1]['id']] );
	}


	public function testPostControllerException()
	{
		$object = $this->getObject( 'addAddressItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->getObject( 'addAddressItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->getObject( 'addAddressItem', $this->throwException( new \Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
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

		$templatePaths = \TestHelperJapi::getTemplatePaths();
		$object = new \Aimeos\Client\JsonApi\Customer\Address\Standard( $this->context, $this->view, $templatePaths, 'customer/address' );

		return $object;
	}
}