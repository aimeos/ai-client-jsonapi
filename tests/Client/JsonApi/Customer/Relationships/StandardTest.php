<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2025
 */


namespace Aimeos\Client\JsonApi\Customer\Relationships;


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

		$this->object = new \Aimeos\Client\JsonApi\Customer\Relationships\Standard( $this->context );
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
		$customer = $custManager->find( 'test@example.com', ['product'] )->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer->setId( null ) );
		$this->context->setUser( $customer );


		$params = array( 'id' => $customer->getId() );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "product", "id": ' . $customer->getListItems()->first()->getId() . '}}';
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
		$customer = $custManager->find( 'test@example.com', ['product'] )->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer->setId( null ) );
		$this->context->setUser( $customer );


		$params = ['id' => $customer->getId(), 'relatedid' => $customer->getListItems()->first()->getId()];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
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
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
		$this->context->setUser( $customer );

		$params = ['id' => $customer->getId(), 'include' => 'product'];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertNotNull( $result['data'][0]['id'] );
		$this->assertEquals( 'customer.lists', $result['data'][0]['type'] );
		$this->assertGreaterThan( 8, count( $result['data'][0]['attributes'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com', ['product'] );
		$this->context->setUser( $customer );

		$params = array(
			'id' => $customer->getId(),
			'related' => 'relationships',
			'relatedid' => $customer->getListItems()->first()->getId(),
			'fields' => array( 'customer.lists' => 'customer.lists.id,customer.lists.refid' ),
			'include' => 'product',
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer.lists', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['attributes'] ) );
		$this->assertNotNull( $result['data']['id'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Exception() ) );

		$params = array( 'relatedid' => -1 );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$custManager = \Aimeos\MShop::create( $this->context, 'customer' );
		$customer = $custManager->find( 'test@example.com', ['product'] )->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer->setId( null ) );
		$id = $customer->getListItems( 'product', 'favorite' )->first()->getId();
		$this->context->setUser( $customer );


		$params = ['id' => $customer->getId(), 'relatedid' => $id, 'include' => 'product'];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "product", "attributes": {
			"customer.lists.config": {"key": "value"}
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer.lists', $result['data']['type'] );
		$this->assertGreaterThan( 8, count( $result['data']['attributes'] ) );
		$this->assertEquals( ['key' => 'value'], $result['data']['attributes']['customer.lists.config'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchControllerException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Exception() ) );

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
		$customer = $custManager->save( $customer->setId( null ) );
		$this->context->setUser( $customer );


		$params = ['id' => $customer->getId(), 'include' => 'product'];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "product", "attributes": {
			"customer.lists.domain": "product", "customer.lists.type": "favorite", "customer.lists.refid": "-1"
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'customer.lists', $result['data'][0]['type'] );
		$this->assertEquals( '-1', $result['data'][0]['attributes']['customer.lists.refid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMultiple()
	{
		$custManager = \Aimeos\MShop::create( $this->context, 'customer' );
		$customer = $custManager->create()->setCode( 'unittest-jsonapi' );
		$customer = $custManager->save( $customer->setId( null ) );
		$this->context->setUser( $customer );


		$params = ['id' => $customer->getId(), 'include' => 'product'];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": [{"type": "product", "attributes": {
			"customer.lists.domain": "product", "customer.lists.type": "favorite", "customer.lists.refid": "-1"
		}}, {"type": "product", "attributes": {
			"customer.lists.domain": "product", "customer.lists.type": "favorite", "customer.lists.refid": "-2"
		}}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$custManager->delete( $customer->getId() );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertNotNull( $result['data'][0]['id'] );
		$this->assertEquals( 'customer.lists', $result['data'][0]['type'] );
		$this->assertLessThan( 0, $result['data'][0]['attributes']['customer.lists.refid'] );
		$this->assertLessThan( 0, $result['data'][1]['attributes']['customer.lists.refid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostControllerException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\Controller\Frontend\Customer\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$body = '{"data": {"attributes": []}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->object( 'uses', $this->throwException( new \Exception() ) );

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
	protected function object( $method, $result )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Customer\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend::inject( \Aimeos\Controller\Frontend\Customer\Standard::class, $cntl );

		$object = new \Aimeos\Client\JsonApi\Customer\Relationships\Standard( $this->context, 'customer/relationships' );
		$object->setView( $this->view );

		return $object;
	}
}
