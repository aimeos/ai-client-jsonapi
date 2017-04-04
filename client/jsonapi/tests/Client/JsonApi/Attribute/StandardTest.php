<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi\Attribute;


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

		$this->object = new \Aimeos\Client\JsonApi\Attribute\Standard( $this->context, $this->view, $templatePaths, 'attribute' );
	}


	public function testGetItem()
	{
		$attrManager = \Aimeos\MShop\Factory::createManager( $this->context, 'attribute' );
		$attrId = $attrManager->findItem( 'xs', [], 'product', 'size' )->getId();

		$params = array(
			'id' => $attrId,
			'fields' => array(
				'attribute' => 'attribute.id,attribute.label'
			),
			'sort' => 'attribute.id',
			'include' => 'media,price,text'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'attribute', $result['data']['type'] );
		$this->assertEquals( 3, count( $result['data']['relationships']['text']['data'] ) );
		$this->assertEquals( 1, count( $result['data']['relationships']['price']['data'] ) );
		$this->assertEquals( 1, count( $result['data']['relationships']['media']['data'] ) );
		$this->assertEquals( 5, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$this->context->getConfig()->set( 'client/jsonapi/attribute/types', ['size', 'length', 'width'] );

		$params = array(
			'fields' => array(
				'attribute' => 'attribute.id,attribute.type,attribute.code'
			),
			'include' => 'media,price,text',
			'sort' => 'attribute.position',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 17, $result['meta']['total'] );
		$this->assertEquals( 17, count( $result['data'] ) );
		$this->assertEquals( 'attribute', $result['data'][0]['type'] );
		$this->assertEquals( 3, count( $result['data'][0]['attributes'] ) );
		$this->assertEquals( 'size', $result['data'][0]['attributes']['attribute.type'] );
		$this->assertEquals( 'xs', $result['data'][0]['attributes']['attribute.code'] );
		$this->assertEquals( 21, count( $result['included'] ) );

		foreach( $result['data'] as $entry ) {
			$this->assertContains( $entry['attributes']['attribute.type'], ['size', 'length', 'width'] );
		}

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemsCriteria()
	{
		$params = array(
			'filter' => array(
				'==' => array( 'attribute.type.code' => 'size' ),
			),
			'sort' => 'attribute.position',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 6, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Attribute\Standard' )
			->setConstructorArgs( [$this->context, $this->view, $templatePaths, 'attribute'] )
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

		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Attribute\Standard' )
			->setConstructorArgs( [$this->context, $this->view, $templatePaths, 'attribute'] )
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