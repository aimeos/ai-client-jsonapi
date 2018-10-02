<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi\Customer\Relationships;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->object = new \Aimeos\Client\JsonApi\Customer\Relationships\Standard( $this->context, 'customer/relationships' );
		$this->object->setView( $this->view );
	}


	protected function tearDown()
	{
		\Aimeos\Controller\Frontend\Customer\Factory::injectController( '\Aimeos\Controller\Frontend\Customer\Standard', null );
	}


	public function testDelete()
	{
		$custManager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' );
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists' );
		$typeManager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists/type' );

		$userId = $custManager->findItem( 'UTC001' )->getId();
		$typeId = $typeManager->findItem( 'favorite', [], 'product' )->getId();;

		$this->context->setUserId( $userId );
		$item = $manager->createItem()->setParentId( $userId );
		$item->setDomain( 'product' )->setRefId( -1 )->setTypeId( $typeId );
		$item = $manager->saveItem( $item );


		$params = array( 'id' => $userId );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "product", "id": ' . $item->getId() . '}}';
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
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists' );
		$typeManager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists/type' );

		$userId = $custManager->findItem( 'UTC001' )->getId();
		$typeId = $typeManager->findItem( 'favorite', [], 'product' )->getId();;

		$this->context->setUserId( $userId );
		$item = $manager->createItem()->setParentId( $userId );
		$item->setDomain( 'product' )->setRefId( -1 )->setTypeId( $typeId );
		$item = $manager->saveItem( $item );


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
		$object = $this->getObject( 'deleteListItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

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
		$object = $this->getObject( 'deleteListItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

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
		$object = $this->getObject( 'deleteListItem', $this->throwException( new \Exception() ) );

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

		$this->assertEquals( 3, $result['meta']['total'] );
		$this->assertNotNull( $result['data'][0]['id'] );
		$this->assertEquals( 'customer/lists', $result['data'][0]['type'] );
		$this->assertGreaterThan( 8, count( $result['data'][0]['attributes'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$user = \Aimeos\MShop\Factory::createManager( $this->context, 'customer' )->findItem( 'UTC001', ['product'] );
		$this->context->setUserId( $user->getId() );
		$listsItems = $user->getListItems();

		if( ( $listsItem = reset( $listsItems ) ) === false ) {
			throw new \RuntimeException( 'No lists item found for "UTC001"' );
		}

		$params = array(
			'id' => $user->getId(),
			'related' => 'relationships',
			'relatedid' => $listsItem->getId(),
			'fields' => array( 'customer/lists' => 'customer.lists.id,customer.lists.refid' ),
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/lists', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['attributes'] ) );
		$this->assertNotNull( $result['data']['id'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$object = $this->getObject( 'getListItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

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
		$object = $this->getObject( 'getListItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

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
		$object = $this->getObject( 'getListItem', $this->throwException( new \Exception() ) );

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
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists' );
		$typeManager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists/type' );

		$userId = $custManager->findItem( 'UTC001' )->getId();
		$typeId = $typeManager->findItem( 'favorite', [], 'product' )->getId();;

		$this->context->setUserId( $userId );
		$item = $manager->createItem()->setParentId( $userId );
		$item->setDomain( 'product' )->setRefId( -1 )->setTypeId( $typeId );
		$item = $manager->saveItem( $item );

		$params = array( 'id' => $item->getId(), 'relatedid' => $item->getId() );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "product", "attributes": {
			"customer.lists.domain": "product", "customer.lists.type": "favorite", "customer.lists.refid": "-2"
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$manager->deleteItem( $item->getId() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/lists', $result['data']['type'] );
		$this->assertGreaterThan( 8, count( $result['data']['attributes'] ) );
		$this->assertEquals( '-2', $result['data']['attributes']['customer.lists.refid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchControllerException()
	{
		$object = $this->getObject( 'editListItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->getObject( 'editListItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->getObject( 'editListItem', $this->throwException( new \Exception() ) );

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

		$body = '{"data": {"type": "product", "attributes": {
			"customer.lists.domain": "product", "customer.lists.type": "favorite", "customer.lists.refid": "-1"
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer/lists', $result['data'][0]['type'] );
		$this->assertEquals( '-1', $result['data'][0]['attributes']['customer.lists.refid'] );

		$this->assertArrayNotHasKey( 'errors', $result );


		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists' );
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

		$body = '{"data": [{"type": "product", "attributes": {
			"customer.lists.domain": "product", "customer.lists.type": "favorite", "customer.lists.refid": "-1"
		}}, {"type": "product", "attributes": {
			"customer.lists.domain": "product", "customer.lists.type": "favorite", "customer.lists.refid": "-2"
		}}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertNotNull( $result['data'][0]['id'] );
		$this->assertEquals( 'customer/lists', $result['data'][0]['type'] );
		$this->assertEquals( '-1', $result['data'][0]['attributes']['customer.lists.refid'] );
		$this->assertEquals( '-2', $result['data'][1]['attributes']['customer.lists.refid'] );

		$this->assertArrayNotHasKey( 'errors', $result );


		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'customer/lists' );
		$manager->deleteItems( [$result['data'][0]['id'], $result['data'][1]['id']] );
	}


	public function testPostControllerException()
	{
		$object = $this->getObject( 'addListItem', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->getObject( 'addListItem', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->getObject( 'addListItem', $this->throwException( new \Exception() ) );

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
		$this->assertEquals( 7, count( $result['meta']['attributes'] ) );
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

		$object = new \Aimeos\Client\JsonApi\Customer\Relationships\Standard( $this->context, 'customer/relationships' );
		$object->setView( $this->view );

		return $object;
	}
}
