<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
 */


namespace Aimeos\Client\JsonApi\Basket\Service;


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

		$this->object = new \Aimeos\Client\JsonApi\Basket\Service\Standard( $this->context );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testDelete()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$servId = $manager->find( 'unitdeliverycode', [], 'service', 'delivery' )->getId();

		$body = '{"data": {"type": "basket.service", "id": "delivery", "attributes": {"service.id": ' . $servId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 1, count( $result['data']['relationships']['basket.service']['data'] ) );


		$body = '{"data": {"type": "basket.service", "id": "delivery"}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertArrayNotHasKey( 'basket.service', $result['data']['relationships'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeleteById()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$servId = $manager->find( 'unitdeliverycode', [], 'service', 'delivery' )->getId();

		$body = '{"data": {"type": "basket.service", "id": "delivery", "attributes": {"service.id": ' . $servId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 1, count( $result['data']['relationships']['basket.service']['data'] ) );


		$params = array( 'id' => 'default', 'relatedid' => 'delivery' );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->delete( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertArrayNotHasKey( 'basket.service', $result['data']['relationships'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testDeletePluginException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteMShopException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testDeleteException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$servId = $manager->find( 'directdebit-test', [], 'service', 'payment' )->getId();
		$servId2 = $manager->find( 'unitpaymentcode', [], 'service', 'payment' )->getId();

		$body = '{"data": {"type": "basket.service", "id": "payment", "attributes": {
			"service.id": "' . $servId . '",
			"directdebit.accountowner": "test",
			"directdebit.accountno": "1234",
			"directdebit.bankcode": "5678",
			"directdebit.bankname": "test"
		}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 'directdebit-test', $result['included'][0]['attributes']['order.service.code'] );


		$body = '{"data": {"type": "basket.service", "id": "payment", "attributes": {"service.id": ' . $servId2 . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$params = array( 'id' => 'default', 'relatedid' => 'payment' );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket.service']['data'] ) );
		$this->assertEquals( 'unitpaymentcode', $result['included'][0]['attributes']['order.service.code'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchMultiple()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$servId = $manager->find( 'directdebit-test', [], 'service', 'payment' )->getId();
		$servId2 = $manager->find( 'unitpaymentcode', [], 'service', 'payment' )->getId();

		$body = '{"data": [{
			"type": "basket.service", "id": "payment", "attributes": {
				"service.id": "' . $servId . '",
				"directdebit.accountowner": "test",
				"directdebit.accountno": "1234",
				"directdebit.bankcode": "5678",
				"directdebit.bankname": "test"
			}
		}, {
			"type": "basket.service", "id": "payment", "attributes": {"service.id": ' . $servId2 . '}
		}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$params = array( 'id' => 'default', 'relatedid' => 'payment' );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->patch( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket.service']['data'] ) );
		$this->assertEquals( 'directdebit-test', $result['included'][0]['attributes']['order.service.code'] );
		$this->assertEquals( 'unitpaymentcode', $result['included'][1]['attributes']['order.service.code'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPatchPluginException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $object->patch( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchMShopException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->patch( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatchException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->patch( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$servId = $manager->find( 'unitdeliverycode', [], 'service', 'delivery' )->getId();

		$body = '{"data": {"type": "basket.service", "id": "delivery", "attributes": {"service.id": ' . $servId . '}}}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['basket.service']['data'] ) );
		$this->assertEquals( 'unitdeliverycode', $result['included'][0]['attributes']['order.service.code'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostMultiple()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$servId = $manager->find( 'unitdeliverycode', [], 'service', 'delivery' )->getId();
		$servId2 = $manager->find( 'unitpaymentcode', [], 'service', 'payment' )->getId();

		$body = '{"data": [{
			"type": "basket.service", "id": "delivery", "attributes": {"service.id": ' . $servId . '}
		}, {
			"type": "basket.service", "id": "payment", "attributes": {"service.id": ' . $servId2 . '}
		}]}';
		$request = $this->view->request()->withBody( $this->view->response()->createStreamFromString( $body ) );

		$response = $this->object->post( $request, $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'basket', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['relationships']['basket.service']['data'] ) );
		$this->assertEquals( 'unitdeliverycode', $result['included'][0]['attributes']['order.service.code'] );
		$this->assertEquals( 'unitpaymentcode', $result['included'][1]['attributes']['order.service.code'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testPostPluginException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Plugin\Provider\Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 409, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostMShopException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPostException()
	{
		$object = $this->object( 'setType', $this->throwException( new \Exception() ) );

		$response = $object->post( $this->view->request(), $this->view->response() );
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
		$this->assertEquals( 1, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a test object with a mocked basket controller
	 *
	 * @param string $method Basket controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function object( $method, $result )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Basket\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Basket\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Basket\Service\Standard( $this->context );
		$object->setView( $this->view );

		return $object;
	}
}
