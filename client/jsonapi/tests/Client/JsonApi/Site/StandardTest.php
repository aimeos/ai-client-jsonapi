<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Client\JsonApi\Site;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Site\Standard( $this->context, 'locale/site' );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context, $this->view );
	}


	public function testGetItem()
	{
		$siteId = \Aimeos\MShop::create( $this->context, 'locale/site' )->find( 'unittest' )->getId();
		$params = [
			'id' => $siteId,
			'fields' => [
				'locale/site' => 'locale.site.id,locale.site.label'
			],
			'sort' => 'locale.site.id'
		];

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'locale/site', $result['data']['type'] );
		$this->assertEquals( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$params = [
			'filter' => [
				'==' => ['locale.site.code' => ['unittest']],
			],
			'fields' => [
				'locale/site' => 'locale.site.id,locale.site.label'
			],
			'sort' => 'locale.site.id',
		];

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'locale/site', $result['data'][0]['type'] );
		$this->assertEquals( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemDeep()
	{
		$config = $this->context->getConfig()->set( 'client/jsonapi/site/deep', true );
		$helper = new \Aimeos\MW\View\Helper\Config\Standard( $this->view, $config );
		$this->view->addHelper( 'config', $helper );

		$siteId = \Aimeos\MShop::create( $this->context, 'locale/site' )->find( 'unittest' )->getId();
		$params = [
			'id' => $siteId,
			'fields' => [
				'locale/site' => 'locale.site.id,locale.site.label'
			],
			'sort' => 'locale.site.id',
			'include' => 'locale/site'
		];

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'locale/site', $result['data']['type'] );
		$this->assertGreaterThanOrEqual( 0, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Site\Standard::class )
			->setConstructorArgs( [$this->context, 'locale/site'] )
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
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Site\Standard::class )
			->setConstructorArgs( [$this->context, 'locale/site'] )
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
