<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2025
 */


namespace Aimeos\Client\JsonApi\Attribute;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		\Aimeos\Controller\Frontend::cache( true );
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Attribute\Standard( $this->context );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->object, $this->context );
	}


	public function testGetItem()
	{
		$attrManager = \Aimeos\MShop::create( $this->context, 'attribute' );
		$attrId = $attrManager->find( 'xs', [], 'product', 'size' )->getId();

		$params = array(
			'id' => $attrId,
			'fields' => array(
				'attribute' => 'attribute.id,attribute.label'
			),
			'sort' => 'attribute.id',
			'include' => 'media,price,text,attribute.type'
		);

		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
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
		$this->assertEquals( 1, count( $result['data']['relationships']['attribute.type']['data'] ) );
		$this->assertEquals( 6, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemProperties()
	{
		$attrManager = \Aimeos\MShop::create( $this->context, 'attribute' );
		$attrId = $attrManager->find( 'testurl', [], 'product', 'download' )->getId();

		$params = array(
			'id' => $attrId,
			'fields' => array(
				'attribute' => 'attribute.id,attribute.property.value,attribute.property.type.code'
			),
			'sort' => 'attribute.id',
			'include' => 'attribute/property'
		);

		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'attribute', $result['data']['type'] );
		$this->assertEquals( 2, count( $result['data']['relationships']['attribute.property']['data'] ) );
		$this->assertEquals( 2, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$this->context->config()->set( 'client/jsonapi/attribute/types', ['size', 'length', 'width'] );

		$params = array(
			'fields' => array(
				'attribute' => 'attribute.id,attribute.type,attribute.code'
			),
			'include' => 'media,price,text',
			'sort' => '-attribute.type,attribute.position',
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
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
		$this->assertEquals( 23, count( $result['included'] ) );

		foreach( $result['data'] as $entry ) {
			$this->assertContains( $entry['attributes']['attribute.type'], ['size', 'length', 'width'] );
		}

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemsCriteria()
	{
		$params = array(
			'filter' => array(
				'==' => array( 'attribute.type' => 'size' ),
			),
			'sort' => 'attribute.position',
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 6, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Attribute\Standard::class )
			->setConstructorArgs( [$this->context, 'attribute'] )
			->onlyMethods( ['getItems'] )
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
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Attribute\Standard::class )
			->setConstructorArgs( [$this->context, 'attribute'] )
			->onlyMethods( ['getItems'] )
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
