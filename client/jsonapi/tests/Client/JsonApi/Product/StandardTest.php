<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi\Product;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->object = new \Aimeos\Client\JsonApi\Product\Standard( $this->context, 'product' );
		$this->object->setView( $this->view );
	}


	public function testGetView()
	{
		$this->assertInstanceOf( '\Aimeos\MW\View\Iface', $this->object->getView() );
	}


	public function testSetView()
	{
		$result = $this->object->setView( $this->view );
		$this->assertInstanceOf( '\Aimeos\Client\JsonApi\Iface', $result );
	}


	public function testAggregateAttribute()
	{
		$params = array( 'aggregate' => 'index.attribute.id' );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 15, $result['meta']['total'] );
		$this->assertEquals( 15, count( $result['data'] ) );
		$this->assertGreaterThan( 0, $result['data'][0]['id'] );
		$this->assertGreaterThan( 0, $result['data'][0]['attributes'] );
		$this->assertEquals( 'index.attribute.id', $result['data'][0]['type'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testAggregateCatalog()
	{
		$params = array( 'aggregate' => 'index.catalog.id' );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 4, $result['meta']['total'] );
		$this->assertEquals( 4, count( $result['data'] ) );
		$this->assertGreaterThan( 0, $result['data'][0]['id'] );
		$this->assertGreaterThan( 0, $result['data'][0]['attributes'] );
		$this->assertEquals( 'index.catalog.id', $result['data'][0]['type'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItem()
	{
		$prodId = \Aimeos\MShop\Factory::createManager( $this->context, 'product' )->findItem( 'CNE' )->getId();
		$params = array(
			'id' => $prodId,
			'fields' => array(
				'product' => 'product.id,product.label'
			),
			'sort' => 'product.id',
			'include' => 'attribute,media,price,product,product/property,text'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'product', $result['data']['type'] );
		$this->assertEquals( 7, count( $result['data']['relationships']['text']['data'] ) );
		$this->assertEquals( 2, count( $result['data']['relationships']['price']['data'] ) );
		$this->assertEquals( 3, count( $result['data']['relationships']['media']['data'] ) );
		$this->assertEquals( 4, count( $result['data']['relationships']['product/property']['data'] ) );
		$this->assertEquals( 5, count( $result['data']['relationships']['product']['data'] ) );
		$this->assertEquals( 6, count( $result['data']['relationships']['attribute']['data'] ) );
		$this->assertGreaterThanOrEqual( 66, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$catId = \Aimeos\MShop\Factory::createManager( $this->context, 'catalog' )->findItem( 'cafe' )->getId();
		$params = array(
			'filter' => array( 'f_catid' => $catId ),
			'fields' => array(
				'product' => 'product.id,product.code,product.label'
			),
			'sort' => '-code',
			'include' => 'attribute,text,product,product/property'
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
		$this->assertEquals( 'product', $result['data'][0]['type'] );
		$this->assertEquals( 3, count( $result['data'][0]['attributes'] ) );
		$this->assertEquals( 7, count( $result['data'][0]['relationships']['text']['data'] ) );
		$this->assertEquals( 4, count( $result['data'][0]['relationships']['product/property']['data'] ) );
		$this->assertEquals( 6, count( $result['data'][0]['relationships']['attribute']['data'] ) );
		$this->assertEquals( 5, count( $result['data'][0]['relationships']['product']['data'] ) );
		$this->assertGreaterThanOrEqual( 65, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemsCriteria()
	{
		$catId = \Aimeos\MShop\Factory::createManager( $this->context, 'catalog' )->findItem( 'cafe' )->getId();
		$params = array(
			'filter' => array(
				'f_catid' => $catId,
				'f_search' => 'Cafe',
				'f_listtype' => ['unittype13', 'unittype19'],
				'==' => array( 'product.type.code' => 'default' ),
			),
			'sort' => '-product.id',
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
		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Product\Standard' )
			->setConstructorArgs( [$this->context, 'product'] )
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
		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Product\Standard' )
			->setConstructorArgs( [$this->context, 'product'] )
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
		$this->assertEquals( 7, count( $result['meta']['filter'] ) );
		$this->assertEquals( 4, count( $result['meta']['sort'] ) );
		$this->assertArrayNotHasKey( 'attributes', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}
}