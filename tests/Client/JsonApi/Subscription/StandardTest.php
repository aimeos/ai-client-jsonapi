<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2025
 */


namespace Aimeos\Client\JsonApi\Subscription;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $view;


	protected function setUp() : void
	{
		\Aimeos\Controller\Frontend::cache( true );

		$this->context = \TestHelper::context();
		$this->view = $this->context->view();

		$this->context->setUser( \Aimeos\MShop::create( $this->context, 'customer' )->find( 'test@example.com' ) );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend::cache( false );
		unset( $this->view, $this->context );
	}


	public function testCancel()
	{
		$item = $this->getSubscription();
		$params = array( 'id' => $item->getId() );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$this->controller( 'cancel' )->expects( $this->once() )->method( 'cancel' )->willReturn( $item );


		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'subscription', $result['data']['type'] );
		$this->assertGreaterThan( 4, count( $result['data']['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testCancelControllerException()
	{
		$this->controller( 'cancel' )->expects( $this->once() )->method( 'cancel' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testCancelMShopException()
	{
		$this->controller( 'cancel' )->expects( $this->once() )->method( 'cancel' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testCancelException()
	{
		$this->controller( 'cancel' )->expects( $this->once() )->method( 'cancel' )
			->will( $this->throwException( new \Exception() ) );

		$response = $this->object()->delete( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGet()
	{
		$params = array(
			'fields' => array( 'subscription' => 'subscription.id,subscription.datenext,subscription.dateend' ),
			'sort' => 'subscription.datenext,-subscription.id'
		);
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertEquals( 'subscription', $result['data'][0]['type'] );
		$this->assertEquals( 3, count( $result['data'][0]['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetById()
	{
		$params = array( 'id' => $this->getSubscription()->getId() );
		$helper = new \Aimeos\Base\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );


		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'subscription', $result['data']['type'] );
		$this->assertGreaterThan( 4, count( $result['data']['attributes'] ) );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetControllerException()
	{
		$this->controller( 'search' )->expects( $this->once() )->method( 'search' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 403, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$this->controller( 'search' )->expects( $this->once() )->method( 'search' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 404, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testGetException()
	{
		$this->controller( 'search' )->expects( $this->once() )->method( 'search' )
			->will( $this->throwException( new \Exception() ) );

		$response = $this->object()->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 500, $response->getStatusCode() );
		$this->assertArrayHasKey( 'errors', $result );
	}


	public function testOptions()
	{
		$response = $this->object()->options( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( null, $result['meta']['prefix'] );
		$this->assertEquals( 0, count( $result['meta']['attributes'] ) );
		$this->assertArrayNotHasKey( 'filter', $result['meta'] );
		$this->assertArrayNotHasKey( 'sort', $result['meta'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'subscription' );

		$search = $manager->filter()->slice( 0, 1 );
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		if( ( $item = $manager->search( $search )->first() ) === null ) {
			throw new \RuntimeException( 'No subscription item found' );
		}

		return $item;
	}


	/**
	 * Returns a mocked subscription controller
	 *
	 * @param array|string $methods Subscription controller method name to mock
	 * @return \Aimeos\Controller\Frontend\Subscription\Standard Mocked subscription controller
	 */
	protected function controller( $methods )
	{
		$cntl = $this->getMockBuilder( \Aimeos\Controller\Frontend\Subscription\Standard::class )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( (array) $methods )
			->getMock();

		\Aimeos\Controller\Frontend::inject( \Aimeos\Controller\Frontend\Subscription\Standard::class, $cntl );

		return $cntl;
	}


	/**
	 * Returns the JSON API client object
	 *
	 * @return \Aimeos\Client\JsonApi\Subscription\Standard JSON API client object
	 */
	protected function object()
	{
		$object = new \Aimeos\Client\JsonApi\Subscription\Standard( $this->context );
		$object->setView( $this->view );

		return $object;
	}
}
