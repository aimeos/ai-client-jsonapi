<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020-2021
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
		$this->view = $this->context->view();

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
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
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
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' );
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


	public function testGetControllerException()
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Review\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['list'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'list' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Review\Exception() ) );

		\Aimeos\Controller\Frontend::inject( 'review', $cntl );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Review\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['list'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'list' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		\Aimeos\Controller\Frontend::inject( 'review', $cntl );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Review\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['list'] )
			->getMock();

		$cntl->expects( $this->once() )->method( 'list' )
			->will( $this->throwException( new \Exception() ) );

		\Aimeos\Controller\Frontend::inject( 'review', $cntl );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$customerId = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' )->getId();
		$this->context->setUserId( $customerId );
		$item = $this->getReviewItem();


		$params = ['id' => -1, 'related' => 'review', 'relatedid' => $item->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "review", "id": "-1", "attributes": {
			"review.orderproductid": "' . $item->getOrderProductId() . '",
			"review.customerid": "' . $customerId . '",
			"review.domain": "product",
			"review.name": "test user",
			"review.comment": "test comment",
			"review.response": "not allowed",
			"review.rating": 10,
			"review.status": 0
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		\Aimeos\MShop::create( $this->context, 'review' )->save( $item->setModified() );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertNotNull( $result['data']['attributes']['review.refid'] );
		$this->assertEquals( 'product', $result['data']['attributes']['review.domain'] );
		$this->assertEquals( 'test user', $result['data']['attributes']['review.name'] );
		$this->assertEquals( 'test comment', $result['data']['attributes']['review.comment'] );
		$this->assertEquals( 'owner response', $result['data']['attributes']['review.response'] );
		$this->assertEquals( '5', $result['data']['attributes']['review.rating'] );
		$this->assertEquals( '-1', $result['data']['attributes']['review.status'] );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchDenied()
	{
		$this->context->setUserId( -1 );

		$params = ['id' => -2, 'related' => 'review', 'relatedid' => -1];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "review", "id": "-1", "attributes": {
			"review.domain": "test"
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 0, $result['meta']['total'] );
		$this->assertArrayHasKey( 'errors', $result );
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
		$customerId = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' )->getId();
		$this->context->setUserId( $customerId );

		$manager = \Aimeos\MShop::create( $this->context, 'order/base/product' );
		$filter = $manager->filter()->add( ['order.base.product.prodcode' => 'ABCD'] );
		$item = $manager->search( $filter )->first( new \RuntimeException( 'Order product item not found' ) );

		$params = ['id' => -1, 'related' => 'review'];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": {"type": "review", "attributes": {
			"review.orderproductid": "' . $item->getId() . '",
			"review.customerid": "' . $customerId . '",
			"review.domain": "product",
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

		$this->assertNotNull( $result['data'][0]['attributes']['review.refid'] );
		$this->assertEquals( 'product', $result['data'][0]['attributes']['review.domain'] );
		$this->assertEquals( 'test user', $result['data'][0]['attributes']['review.name'] );
		$this->assertEquals( 'test comment', $result['data'][0]['attributes']['review.comment'] );
		$this->assertEquals( '', $result['data'][0]['attributes']['review.response'] );
		$this->assertEquals( '0', $result['data'][0]['attributes']['review.rating'] );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMultiple()
	{
		$customerId = \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' )->getId();
		$this->context->setUserId( $customerId );

		$manager = \Aimeos\MShop::create( $this->context, 'order/base/product' );
		$items = $manager->search( $manager->filter()->add( ['order.base.product.prodcode' => 'ABCD'] ) );

		$params = ['id' => -1, 'related' => 'review'];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$body = '{"data": [{
			"type": "review", "attributes": {
				"review.orderproductid": "' . $items->first()->getId() . '",
				"review.customerid": "' . $customerId . '",
				"review.domain": "product",
				"review.comment": "test comment"
			}
		}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );


		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		\Aimeos\MShop::create( $this->context, 'review' )->delete( $result['data'][0]['id'] );

		$this->assertNotNull( $result['data'][0]['attributes']['review.refid'] );
		$this->assertEquals( 'product', $result['data'][0]['attributes']['review.domain'] );
		$this->assertEquals( 'test comment', $result['data'][0]['attributes']['review.comment'] );

		$this->assertEquals( 1, $result['meta']['total'] );
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


	protected function getReviewItem()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'review' );

		$search = $manager->filter()->slice( 0, 1 );
		$search->setConditions( $search->and( [
			$search->compare( '==', 'review.domain', 'product' ),
			$search->compare( '>', 'review.status', 0 )
		] ) );

		return $manager->search( $search )->first( new \RuntimeException( 'No review item found' ) );
	}
}
