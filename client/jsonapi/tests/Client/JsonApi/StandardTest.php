<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi;


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

		$this->object = new \Aimeos\Client\JsonApi\Standard( $this->context, $this->view, $templatePaths, '' );
	}


	public function testOptions()
	{
		$response = $this->object->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( null, $result['meta']['prefix'] );
		$this->assertGreaterThan( 8, count( $result['meta']['resources'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testOptionsException()
	{
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Standard' )
			->setConstructorArgs( [$this->context, $this->view, $templatePaths, ''] )
			->setMethods( ['getContext'] )
			->getMock();

		$object->expects( $this->once() )->method( 'getContext' )
			->will( $this->throwException( new \Exception() ) );


		$response = $object->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}
}