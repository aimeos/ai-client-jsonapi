<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Stock;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Stock\Standard( $this->context, 'stock' );
		$this->object->setView( $this->view );
	}


	public function testGetItem()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'stock' );
		$stockId = $manager->search( $manager->filter()->slice( 0, 1 ) )->getId()->first();

		$params = array(
			'id' => $stockId,
			'fields' => array(
				'stock' => 'stock.id,stock.productid,stock.stocklevel'
			),
			'sort' => 'stock.id'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'stock', $result['data']['type'] );
		$this->assertEquals( 3, count( $result['data']['attributes'] ) );
		$this->assertNotEquals( '', $result['data']['attributes']['stock.productid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$prodId = \Aimeos\MShop::create( $this->context, 'product' )->find( 'CNC' )->getId();

		$params = array(
			'filter' => ['s_prodid' => [$prodId]],
			'sort' => 'stock.productid,-stock.dateback',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 1, count( $result['data'] ) );
		$this->assertEquals( 'stock', $result['data'][0]['type'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Stock\Standard::class )
			->setConstructorArgs( [$this->context, 'stock'] )
			->setMethods( ['getItems'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getItems' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$object->setView( $this->view );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Stock\Standard::class )
			->setConstructorArgs( [$this->context, 'stock'] )
			->setMethods( ['getItems'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getItems' )
			->will( $this->throwException( new \Exception() ) );

		$object->setView( $this->view );

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
		$this->assertEquals( 3, count( $result['meta']['filter'] ) );
		$this->assertArrayNotHasKey( 'attributes', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}
}
