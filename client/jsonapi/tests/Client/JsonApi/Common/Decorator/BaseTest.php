<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi\Common\Decorator;


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $stub;
	private $view;


	protected function setUp()
	{
		$context = \TestHelperJapi::getContext();
		$this->view = $context->getView();

		$this->stub = $this->getMockBuilder( '\\Aimeos\\Client\\JsonApi\\Standard' )
			->setConstructorArgs( [$context, $this->view, [], 'attribute'] )
			->getMock();

		$this->object = $this->getMockBuilder( '\\Aimeos\\Client\\JsonApi\\Common\\Decorator\Base' )
			->setConstructorArgs( [$this->stub, $context, $this->view, [], ''] )
			->getMockForAbstractClass();
	}


	protected function tearDown()
	{
		unset( $this->object, $this->stub, $this->view );
	}


	public function testDelete()
	{
		$this->stub->expects( $this->once() )->method( 'delete' )->will( $this->returnArgument( 1 ) );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->delete( $this->view->request(), $response ) );
	}


	public function testGet()
	{
		$this->stub->expects( $this->once() )->method( 'get' )->will( $this->returnArgument( 1 ) );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->get( $this->view->request(), $response ) );
	}


	public function testPatch()
	{
		$this->stub->expects( $this->once() )->method( 'patch' )->will( $this->returnArgument( 1 ) );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->patch( $this->view->request(), $response ) );
	}


	public function testPost()
	{
		$this->stub->expects( $this->once() )->method( 'post' )->will( $this->returnArgument( 1 ) );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->post( $this->view->request(), $response ) );
	}


	public function testPut()
	{
		$this->stub->expects( $this->once() )->method( 'put' )->will( $this->returnArgument( 1 ) );
		$response = $this->view->response();

		$this->assertSame( $response, $this->object->put( $this->view->request(), $response ) );
	}


	public function testOptions()
	{
		$this->stub->expects( $this->once() )->method( 'options' )->will( $this->returnArgument( 1 ) );
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
		$class = new \ReflectionClass( '\Aimeos\Client\JsonApi\Common\Decorator\Base' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
