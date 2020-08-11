<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020
 */


namespace Aimeos\Client\JsonApi\Customer\Review;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		\Aimeos\Controller\Frontend::cache( true );
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->object = new \Aimeos\Client\JsonApi\Customer\Review\Standard( $this->context, 'customer/review' );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testDelete()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'review' );
		$item = $manager->create( ['review.domain' => 'product', 'review.refid' => '-1', 'review.customerid' => '-1'] );
		$item = $manager->save( $item->setId( null ) );

		$this->context->setUserId( -1 );


		$params = ['id' => -1, 'related' => 'review'];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": [{"type": "review", "id": "' . $item->getId() . '"}]}';
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
		$manager = \Aimeos\MShop::create( $this->context, 'review' );
		$item = $manager->create( ['review.domain' => 'product', 'review.refid' => '-1', 'review.customerid' => '-1'] );
		$item = $manager->save( $item->setId( null ) );

		$this->context->setUserId( -1 );


		$params = ['id' => -1, 'related' => 'review', 'relatedid' => $item->getId()];
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
		$mock = $this->getObject( 'getBody', $this->throwException( new \Aimeos\Controller\Frontend\Review\Exception() ) );
		$response = $this->object->delete( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Aimeos\MShop\Exception() ) );
		$response = $this->object->delete( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Exception() ) );
		$response = $this->object->delete( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->findItem( 'test@example.com' );
		$this->context->setUserId( $customer->getId() );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 4, $result['meta']['total'] );
		$this->assertEquals( 'review', $result['data'][0]['type'] );
		$this->assertNotNull( $result['data'][0]['id'] );
		$this->assertGreaterThan( 3, count( $result['data'][0]['attributes'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->findItem( 'test@example.com' );
		$this->context->setUserId( $customer->getId() );

		$manager = \Aimeos\MShop::create( $this->context, 'review' );
		$item = $manager->search( $manager->filter()->add( 'review.customerid', '==', $customer->getId() ) )->first();


		$params = array(
			'id' => $customer->getId(),
			'related' => 'review',
			'relatedid' => $item->getId(),
			'fields' => ['review' => 'review.id,review.rating'],
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'review', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['attributes'] ) );
		$this->assertNotNull( $result['data']['id'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetByIdDenied()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'review' );
		$item = $manager->search( $manager->filter() )->first();


		$params = array(
			'id' => -1,
			'related' => 'review',
			'relatedid' => $item->getId(),
			'fields' => ['review' => 'review.id,review.rating'],
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$manager = $this->getMockBuilder( \Aimeos\MShop\Review\Manager\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['search'] )
			->getMock();

		$manager->expects( $this->once() )->method( 'search' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Review\Exception() ) );

		\Aimeos\MShop::inject( 'review', $manager );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$manager = $this->getMockBuilder( \Aimeos\MShop\Review\Manager\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['search'] )
			->getMock();

		$manager->expects( $this->once() )->method( 'search' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		\Aimeos\MShop::inject( 'review', $manager );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$manager = $this->getMockBuilder( \Aimeos\MShop\Review\Manager\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['search'] )
			->getMock();

		$manager->expects( $this->once() )->method( 'search' )
			->will( $this->throwException( new \Exception() ) );

		\Aimeos\MShop::inject( 'review', $manager );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'review' );
		$item = $manager->create( [
			'review.customerid' => '-1',
			'review.domain' => 'product',
			'review.refid' => '-1',
			'review.response' => 'none'
		] );
		$item = $manager->save( $item->setId( null ) );

		$this->context->setUserId( -1 );


		$params = ['id' => -1, 'related' => 'review', 'relatedid' => $item->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "review", "id": "-1", "attributes": {
			"review.orderproductid": "123",
			"review.customerid": -2,
			"review.domain": "test",
			"review.refid": "456",
			"review.name": "test user",
			"review.comment": "test comment",
			"review.response": "not allowed",
			"review.rating": 10,
			"review.status": 0
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$manager->delete( $item );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 'test', $result['data']['attributes']['review.domain'] );
		$this->assertEquals( '456', $result['data']['attributes']['review.refid'] );
		$this->assertEquals( 'test user', $result['data']['attributes']['review.name'] );
		$this->assertEquals( 'test comment', $result['data']['attributes']['review.comment'] );
		$this->assertEquals( 'none', $result['data']['attributes']['review.response'] );
		$this->assertEquals( '5', $result['data']['attributes']['review.rating'] );
		$this->assertEquals( '1', $result['data']['attributes']['review.status'] );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchControllerException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Aimeos\Controller\Frontend\Review\Exception() ) );
		$response = $this->object->patch( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Aimeos\MShop\Exception() ) );
		$response = $this->object->patch( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Exception() ) );
		$response = $this->object->patch( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$this->context->setUserId( -1 );

		$params = ['id' => -1, 'related' => 'review'];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "review", "attributes": {
			"review.orderproductid": "123",
			"review.customerid": -2,
			"review.domain": "test",
			"review.refid": "456",
			"review.name": "test user",
			"review.comment": "test comment",
			"review.response": "not allowed",
			"review.rating": -1,
			"review.status": 0
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		\Aimeos\MShop::create( $this->context, 'review' )->delete( $result['data'][0]['id'] );

		$this->assertEquals( 'test', $result['data'][0]['attributes']['review.domain'] );
		$this->assertEquals( '456', $result['data'][0]['attributes']['review.refid'] );
		$this->assertEquals( 'test user', $result['data'][0]['attributes']['review.name'] );
		$this->assertEquals( 'test comment', $result['data'][0]['attributes']['review.comment'] );
		$this->assertEquals( '', $result['data'][0]['attributes']['review.response'] );
		$this->assertEquals( '0', $result['data'][0]['attributes']['review.rating'] );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMultiple()
	{
		$this->context->setUserId( -1 );

		$params = ['id' => -1, 'related' => 'review'];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": [{
			"type": "review", "attributes": {
				"review.domain": "test",
				"review.refid": "456",
				"review.comment": "test comment"
			}
		}, {
			"type": "review", "attributes": {
				"review.domain": "test",
				"review.refid": "789",
				"review.comment": "more comment"
			}
		}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		\Aimeos\MShop::create( $this->context, 'review' )
			->delete( [$result['data'][0]['id'], $result['data'][1]['id']] );

		$this->assertEquals( 'test', $result['data'][0]['attributes']['review.domain'] );
		$this->assertEquals( '456', $result['data'][0]['attributes']['review.refid'] );
		$this->assertEquals( 'test comment', $result['data'][0]['attributes']['review.comment'] );
		$this->assertEquals( 'test', $result['data'][1]['attributes']['review.domain'] );
		$this->assertEquals( '789', $result['data'][1]['attributes']['review.refid'] );
		$this->assertEquals( 'more comment', $result['data'][1]['attributes']['review.comment'] );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostControllerException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Aimeos\Controller\Frontend\Review\Exception() ) );
		$response = $this->object->post( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Aimeos\MShop\Exception() ) );
		$response = $this->object->post( $mock, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$mock = $this->getObject( 'getBody', $this->throwException( new \Exception() ) );
		$response = $this->object->post( $mock, $this->view->response() );
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
		$this->assertEquals( 5, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a test object with a mocked review manager
	 *
	 * @param string $method Review manager method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function getObject( $method, $result )
	{
		$object = $this->getMockForAbstractClass( \Psr\Http\Message\ServerRequestInterface::class );
		$object->expects( $this->once() )->method( $method )->will( $result );

		return $object;
	}
}
