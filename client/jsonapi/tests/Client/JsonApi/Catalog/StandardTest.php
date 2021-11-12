<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Catalog;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Catalog\Standard( $this->context, 'catalog' );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context, $this->view );
	}


	public function testGetItem()
	{
		$catId = \Aimeos\MShop::create( $this->context, 'catalog' )->find( 'cafe' )->getId();
		$params = array(
			'id' => $catId,
			'fields' => array(
				'catalog' => 'catalog.id,catalog.label'
			),
			'sort' => 'catalog.id',
			'include' => 'catalog,media,text'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'catalog', $result['data']['type'] );
		$this->assertEquals( 1, count( $result['data']['relationships']['text']['data'] ) );
		$this->assertEquals( 2, count( $result['data']['relationships']['media']['data'] ) );
		$this->assertEquals( 3, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$params = array(
			'filter' => [
				'==' => ['catalog.code' => ['cafe', 'tea']],
			],
			'fields' => array(
				'catalog' => 'catalog.id,catalog.label'
			),
			'sort' => 'catalog.id',
			'include' => 'media,text'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertEquals( 'catalog', $result['data'][0]['type'] );
		$this->assertEquals( 1, count( $result['data'][0]['relationships']['text']['data'] ) );
		$this->assertEquals( 2, count( $result['data'][0]['relationships']['media']['data'] ) );
		$this->assertEquals( 'catalog', $result['data'][1]['type'] );
		$this->assertEquals( 5, count( $result['data'][1]['relationships']['text']['data'] ) );
		$this->assertEquals( 8, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemNoID()
	{
		$params = array(
			'include' => 'catalog,media'
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'catalog', $result['data']['type'] );
		$this->assertEquals( 'root', $result['data']['attributes']['catalog.code'] );
		$this->assertEquals( 'Root', $result['data']['attributes']['catalog.label'] );
		$this->assertEquals( 2, count( $result['data']['relationships']['catalog']['data'] ) );
		$this->assertEquals( 'catalog', $result['data']['relationships']['catalog']['data'][0]['type'] );
		$this->assertEquals( 3, count( $result['included'] ) );
		$this->assertArrayHaskey( 'self', $result['included'][0]['links'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemNoIdDeep()
	{
		$config = $this->context->getConfig()->set( 'client/jsonapi/catalog/deep', true );
		$helper = new \Aimeos\MW\View\Helper\Config\Standard( $this->view, $config );
		$this->view->addHelper( 'config', $helper );

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, ['include' => 'catalog'] );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 7, count( $result['included'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Catalog\Standard::class )
			->setConstructorArgs( [$this->context, 'catalog'] )
			->setMethods( ['getItem'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getItem' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );


		$object->setView( $this->view );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Catalog\Standard::class )
			->setConstructorArgs( [$this->context, 'catalog'] )
			->setMethods( ['getItem'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getItem' )
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
