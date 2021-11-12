<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020-2021
 */


namespace Aimeos\Client\JsonApi\Review;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Review\Standard( $this->context, 'review' );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend\Review\Factory::injectController( '\Aimeos\Controller\Frontend\Review\Standard', null );
		unset( $this->context, $this->object, $this->view );
	}


	public function testGet()
	{
		$params = array(
			'fields' => ['review' => 'review.id,review.name,review.comment'],
			'sort' => '-rating'
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'review', $result['data'][0]['type'] );
		$this->assertEquals( 3, count( $result['data'][0]['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$params = ['id' => $this->getReview()->getId()];
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'review', $result['data']['type'] );
		$this->assertEquals( 9, count( $result['data']['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$object = $this->getObject( 'search', $this->throwException( new \Aimeos\Controller\Frontend\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getObject( 'search', $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$object = $this->getObject( 'search', $this->throwException( new \Exception() ) );

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
		$this->assertEquals( 0, count( $result['meta']['attributes'] ) );
		$this->assertArrayHasKey( 'filter', $result['meta'] );
		$this->assertArrayHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	/**
	 * Returns a test object with a mocked review controller
	 *
	 * @param string $method Review controller method name to mock
	 * @param mixed $result Return value of the mocked method
	 */
	protected function getObject( $method, $result )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Review\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( [$method] )
			->getMock();

		$cntl->expects( $this->once() )->method( $method )->will( $result );

		\Aimeos\Controller\Frontend\Review\Factory::injectController( '\Aimeos\Controller\Frontend\Review\Standard', $cntl );

		$object = new \Aimeos\Client\JsonApi\Review\Standard( $this->context, 'review' );
		$object->setView( $this->view );


		return $object;
	}


	protected function getReview()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'review' );

		$search = $manager->filter()->slice( 0, 1 );
		$search->setConditions( $search->and( [
			$search->compare( '==', 'review.domain', 'product' ),
			$search->compare( '>', 'review.status', 0 )
		] ) );

		return $manager->search( $search )->first( new \RuntimeException( 'No review item found' ) );
	}
}
