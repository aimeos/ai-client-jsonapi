<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi\Stock;


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

		$this->object = new \Aimeos\Client\JsonApi\Stock\Standard( $this->context, $this->view, $templatePaths, 'stock' );
	}


	public function testGetItem()
	{
		$stockManager = \Aimeos\MShop\Factory::createManager( $this->context, 'stock' );
		$stockId = $stockManager->findItem( 'CNE', [], 'product', 'default' )->getId();

		$params = array(
			'id' => $stockId,
			'fields' => array(
				'stock' => 'stock.id,stock.productcode,stock.stocklevel'
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
		$this->assertEquals( 'CNE', $result['data']['attributes']['stock.productcode'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$params = array(
			'filter' => array( 's_prodcode' => ['CNC', 'CNE'] ),
			'sort' => 'stock.productcode',
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
		$this->assertEquals( 'stock', $result['data'][0]['type'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Stock\Standard' )
			->setConstructorArgs( [$this->context, $this->view, $templatePaths, 'stock'] )
			->setMethods( ['getItems'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getItems' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );


		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Stock\Standard' )
			->setConstructorArgs( [$this->context, $this->view, $templatePaths, 'stock'] )
			->setMethods( ['getItems'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getItems' )
			->will( $this->throwException( new \Exception() ) );


		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}
}