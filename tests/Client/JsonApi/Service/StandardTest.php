<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2025
 */


namespace Aimeos\Client\JsonApi\Service;


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

		$this->object = new \Aimeos\Client\JsonApi\Service\Standard( $this->context );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testGet()
	{
		$params = ['filter' => ['cs_type' => 'payment'], 'include' => ''];
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 3, $result['meta']['total'] );
		$this->assertEquals( 'service', $result['data'][0]['type'] );
		$this->assertGreaterThan( 8, count( $result['data'][0]['attributes'] ) );
		$this->assertArrayHasKey( 'price.costs', $result['data'][0]['attributes']['price'] );
		$this->assertArrayNotHasKey( 'config', $result['data'][0]['attributes'] );
		$this->assertEquals( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$item = $manager->find( 'directdebit-test', [], 'service', 'payment' );

		$params = array(
			'id' => $item->getId(),
			'fields' => array( 'service' => 'service.id,service.code' ),
			'include' => 'service.type',
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'service', $result['data']['type'] );
		$this->assertEquals( 3, count( $result['data']['attributes'] ) );
		$this->assertArrayHasKey( 'price.costs', $result['data']['attributes']['price'] );
		$this->assertEquals( 'directdebit-test', $result['data']['attributes']['service.code'] );
		$this->assertEquals( 4, count( $result['data']['links']['basket.service']['meta'] ) );
		$this->assertArrayHasKey( 'code', $result['data']['links']['basket.service']['meta']['directdebit.accountowner'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['service.type']['data'] ) );
		$this->assertEquals( 1, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetIncluded()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$item = $manager->find( 'unitdeliverycode', [], 'service', 'delivery' );

		$params = array(
			'id' => $item->getId(),
			'include' => 'media,price,text',
			'sort' => 'service.type,-service.position'
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'service', $result['data']['type'] );
		$this->assertArrayHasKey( 'relationships', $result['data'] );
		$this->assertEquals( 3, count( $result['data']['relationships'] ) );
		$this->assertEquals( 8, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->object( 'getProvider', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, ['id' => -1] );
		$this->view->addHelper( 'param', $helper );


		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->object( 'getProvider', $this->throwException( new \Exception() ) );

		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, ['id' => -1] );
		$this->view->addHelper( 'param', $helper );


		$response = $object->get( $this->view->request(), $this->view->response() );
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
		$this->assertEquals( 1, count( $result['meta']['filter'] ) );
		$this->assertArrayNotHasKey( 'attributes', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a test object with a mocked service controller
	 *
	 * @param string $method Service controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function object( $method, $result )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Service\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend::inject( '\Aimeos\Controller\Frontend\Service\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Service\Standard( $this->context, 'service' );
		$object->setView( $this->view );

		return $object;
	}
}
