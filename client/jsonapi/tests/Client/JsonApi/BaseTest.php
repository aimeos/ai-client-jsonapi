<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi;


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Base' )
			->setConstructorArgs( [$this->context, 'test'] )
			->getMockForAbstractClass();

		$this->object->setView( $this->view );
	}


	public function testDelete()
	{
		$response = $this->object->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPatch()
	{
		$response = $this->object->patch( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPost()
	{
		$response = $this->object->post( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testPut()
	{
		$response = $this->object->put( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testOptions()
	{
		$response = $this->object->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetContext()
	{
		$result = $this->access( 'getContext' )->invokeArgs( $this->object, [] );
		$this->assertInstanceOf( '\Aimeos\MShop\Context\Item\Iface', $result );
	}


	public function testGetPath()
	{
		$result = $this->access( 'getPath' )->invokeArgs( $this->object, [] );
		$this->assertEquals( 'test', $result );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( '\Aimeos\Client\JsonApi\Base' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}