<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi\Basket\Address;


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

		$this->object = new \Aimeos\Client\JsonApi\Basket\Address\Standard( $this->context, $this->view, $templatePaths, 'basket/address' );
	}


	protected function tearDown()
	{
		\Aimeos\Controller\Frontend\Basket\Factory::injectController( '\Aimeos\Controller\Frontend\Basket\Standard', null );
	}


	public function testDelete()
	{
		$body = '{"data": {"type": "basket/address", "id": "payment", "attributes": {"order.base.address.firstname": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 1, count( $result['data']['relationships']['basket/address']['data'] ) );


		$body = '{"data": {"type": "basket/address", "id": "payment"}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertArrayNotHasKey( 'basket/address', $result['data']['relationships'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteById()
	{
		$body = '{"data": {"type": "basket/address", "id": "payment", "attributes": {"order.base.address.firstname": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 1, count( $result['data']['relationships']['basket/address']['data'] ) );


		$params = array( 'id' => 'default', 'relatedid' => 'payment' );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertArrayNotHasKey( 'basket/address', $result['data']['relationships'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$body = '{"data": {"type": "basket/address", "id": "payment", "attributes": {"order.base.address.firstname": "test"}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket/address']['data'] ) );
		$this->assertEquals( 'test', $result['included'][0]['attributes']['order.base.address.firstname'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMultiple()
	{
		$body = '{"data": [{
			"type": "basket/address", "id": "payment", "attributes": {"order.base.address.firstname": "test"}
		}, {
			"type": "basket/address", "id": "delivery", "attributes": {"order.base.address.lastname": "test"}
		}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['relationships']['basket/address']['data'] ) );
		$this->assertEquals( 'test', $result['included'][0]['attributes']['order.base.address.firstname'] );
		$this->assertEquals( 'test', $result['included'][1]['attributes']['order.base.address.lastname'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->getObject( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	/**
	 * Returns a test object with a mocked basket controller
	 *
	 * @param string $method Basket controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function getObject( $method, $result )
	{
		$cntl = $this->getMockBuilder( '\Aimeos\Controller\Frontend\Basket\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend\Basket\Factory::injectController( '\Aimeos\Controller\Frontend\Basket\Standard', $cntl );

		$templatePaths = \TestHelperJapi::getTemplatePaths();
		$object = new \Aimeos\Client\JsonApi\Basket\Address\Standard( $this->context, $this->view, $templatePaths, 'basket/address' );

		return $object;
	}
}