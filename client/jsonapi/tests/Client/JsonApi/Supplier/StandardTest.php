<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 */


namespace Aimeos\Client\JsonApi\Supplier;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->object = new \Aimeos\Client\JsonApi\Supplier\Standard( $this->context, 'supplier' );
		$this->object->setView( $this->view );
	}


	public function testGetItem()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'supplier' );
		$supId = $manager->findItem( 'unitCode001' )->getId();

		$params = array(
			'id' => $supId,
			'fields' => array(
				'supplier' => 'supplier.id,supplier.label'
			),
			'sort' => 'supplier.id',
			'include' => 'media,text'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'supplier', $result['data']['type'] );
		$this->assertEquals( 3, count( $result['data']['relationships']['text']['data'] ) );
		$this->assertEquals( 1, count( $result['data']['relationships']['media']['data'] ) );
		$this->assertEquals( 4, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemAddress()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'supplier' );
		$supId = $manager->findItem( 'unitCode001' )->getId();

		$params = array(
			'id' => $supId,
			'fields' => array(
				'supplier' => 'supplier.id,supplier.address.firstname,supplier.address.lastname'
			),
			'sort' => 'supplier.id',
			'include' => 'supplier/address'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'supplier', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['supplier/address']['data'] ) );
		$this->assertEquals( 1, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$params = array(
			'filter' => array(
				'=~' => array( 'supplier.code' => 'unitCode00' ),
			),
			'fields' => array(
				'supplier' => 'supplier.id,supplier.label,supplier.code'
			),
			'include' => 'media,text,supplier/address',
			'sort' => 'supplier.label',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertEquals( 2, count( $result['data'] ) );
		$this->assertEquals( 'supplier', $result['data'][0]['type'] );
		$this->assertEquals( 3, count( $result['data'][0]['attributes'] ) );
		$this->assertEquals( 'unitCode001', $result['data'][0]['attributes']['supplier.code'] );
		$this->assertEquals( 'unitCode002', $result['data'][1]['attributes']['supplier.code'] );
		$this->assertEquals( 6, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemsCriteria()
	{
		$params = array(
			'filter' => array(
				'=~' => array( 'supplier.code' => 'unitCode00' ),
			),
			'sort' => 'supplier.label',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Supplier\Standard' )
			->setConstructorArgs( [$this->context, 'supplier'] )
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
		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Supplier\Standard' )
			->setConstructorArgs( [$this->context, 'supplier'] )
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
		$this->assertArrayNotHasKey( 'attributes', $result['meta'] );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}
}