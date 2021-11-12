<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Standard( $this->context, '' );
		$this->object->setView( $this->view );
	}


	public function testGet()
	{
		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertArrayHasKey( 'title', $result['errors'] );
	}


	public function testOptions()
	{
		$response = $this->object->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( null, $result['meta']['prefix'] );
		$this->assertGreaterThanOrEqual( 12, count( $result['meta']['resources'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testOptionsException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Standard::class )
			->setConstructorArgs( [$this->context, ''] )
			->setMethods( ['getContext'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getContext' )
			->will( $this->throwException( new \Exception() ) );


		$object->setView( $this->view );

		$response = $object->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}
}
