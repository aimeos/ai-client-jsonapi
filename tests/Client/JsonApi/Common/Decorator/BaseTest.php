<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2024
 */


namespace Aimeos\Client\JsonApi\Common\Decorator;


class Example extends Base
{
}


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $stub;
	private $view;


	protected function setUp() : void
	{
		$context = \TestHelper::context();
		$this->view = $context->view();

		$this->stub = $this->getMockBuilder( \Aimeos\Client\JsonApi\Standard::class )
			->setConstructorArgs( [$context, 'attribute'] )
			->getMock();

		$this->object = new \Aimeos\Client\JsonApi\Common\Decorator\Example( $this->stub, $context, '' );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->stub, $this->view );
	}


	public function testDelete()
	{
		$this->stub->expects( $this->once() )->method( 'delete' )->willReturnArgument( 1 );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->delete( $this->view->request(), $response ) );
	}


	public function testGet()
	{
		$this->stub->expects( $this->once() )->method( 'get' )->willReturnArgument( 1 );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->get( $this->view->request(), $response ) );
	}


	public function testPatch()
	{
		$this->stub->expects( $this->once() )->method( 'patch' )->willReturnArgument( 1 );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->patch( $this->view->request(), $response ) );
	}


	public function testPost()
	{
		$this->stub->expects( $this->once() )->method( 'post' )->willReturnArgument( 1 );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->post( $this->view->request(), $response ) );
	}


	public function testPut()
	{
		$this->stub->expects( $this->once() )->method( 'put' )->willReturnArgument( 1 );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->put( $this->view->request(), $response ) );
	}


	public function testOptions()
	{
		$this->stub->expects( $this->once() )->method( 'options' )->willReturnArgument( 1 );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->options( $this->view->request(), $response ) );
	}


	public function testGetClient()
	{
		$result = $this->access( 'getClient' )->invokeArgs( $this->object, [] );
		$this->assertSame( $this->stub, $result );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Client\JsonApi\Common\Decorator\Base::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
