<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Locale;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->object = new \Aimeos\Client\JsonApi\Locale\Standard( $this->context, 'locale' );
		$this->object->setView( $this->view );
	}


	public function testGetItem()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'locale' );

		$search = $manager->filter()->slice( 0, 1 );
		$search->setConditions( $search->compare( '==', 'locale.status', 1 ) );
		$search->setSortations( [$search->sort( '+', 'locale.position' )] );

		if( ( $item = $manager->search( $search )->first() ) === null ) {
			throw new \Exception( 'No locale item found' );
		}


		$params = array(
			'id' => $item->getId(),
			'fields' => array(
				'locale' => 'locale.id,locale.languageid,locale.currencyid'
			)
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'locale', $result['data']['type'] );
		$this->assertGreaterThan( 0, $result['data']['attributes']['locale.id'] );
		$this->assertEquals( 'en', $result['data']['attributes']['locale.languageid'] );
		$this->assertEquals( 'EUR', $result['data']['attributes']['locale.currencyid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItems()
	{
		$params = array( 'sort' => '-locale.languageid,locale.position' );
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 1, count( $result['data'] ) );
		$this->assertEquals( 'locale', $result['data'][0]['type'] );
		$this->assertGreaterThanOrEqual( 6, count( $result['data'][0]['attributes'] ) );
		$this->assertEquals( 'en', $result['data'][0]['attributes']['locale.languageid'] );
		$this->assertEquals( 'EUR', $result['data'][0]['attributes']['locale.currencyid'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemsCriteria()
	{
		$params = array(
			'filter' => array(
				'>=' => array( 'locale.position' => 0 ),
			),
			'sort' => '-locale.position',
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetMShopException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Locale\Standard::class )
			->setConstructorArgs( [$this->context, 'locale'] )
			->setMethods( ['getItems'] )
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
		$object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Locale\Standard::class )
			->setConstructorArgs( [$this->context, 'locale'] )
			->setMethods( ['getItems'] )
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
