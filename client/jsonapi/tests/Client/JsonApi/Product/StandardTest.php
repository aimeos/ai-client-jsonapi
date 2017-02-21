<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi\Product;


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

		$this->object = new \Aimeos\Client\JsonApi\Product\Standard( $this->context, $this->view, $templatePaths, 'product' );
	}


	public function testGetItem()
	{
		$prodId = \Aimeos\MShop\Factory::createManager( $this->context, 'product' )->findItem( 'CNE' )->getId();
		$params = array(
			'id' => $prodId,
			'fields' => array(
				'product' => 'product.id,product.label'
			),
			'sort' => 'product.id',
			'include' => 'attribute,media,price,product,product/property,text'
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );


		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 1, $result['meta']['total'] );
		$this->assertEquals( 'product', $result['data']['type'] );
		$this->assertEquals( 6, count( $result['data']['attributes']['text'] ) );
		$this->assertEquals( 2, count( $result['data']['attributes']['price'] ) );
		$this->assertEquals( 4, count( $result['data']['attributes']['media'] ) );
		$this->assertEquals( 4, count( $result['data']['attributes']['product/property'] ) );
		$this->assertEquals( 5, count( $result['data']['relationships']['product']['data'] ) );
		$this->assertEquals( 5, count( $result['data']['relationships']['attribute']['data'] ) );
		$this->assertEquals( 12, count( $result['included'] ) );
		$this->assertArrayHaskey( 'self', $result['included'][0]['links'] );

		$this->assertArrayNotHasKey( 'errors', $result );
	}


	public function testGetItemsFields()
	{
		$catId = \Aimeos\MShop\Factory::createManager( $this->context, 'catalog' )->findItem( 'cafe' )->getId();
		$params = array(
			'filter' => array( 'f_catid' => $catId ),
			'fields' => array(
				'product' => 'product.id,product.label'
			),
			'sort' => 'product.id',
			'include' => 'text,product,product/property'
		);
		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $params );
		$this->view->addHelper( 'param', $helper );

		$response = $this->object->get( $this->view->request(), $this->view->response() );
		$result = json_decode( (string) $response->getBody(), true );

		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 1, count( $response->getHeader( 'Allow' ) ) );
		$this->assertEquals( 1, count( $response->getHeader( 'Content-Type' ) ) );

		$this->assertEquals( 2, $result['meta']['total'] );
		$this->assertEquals( 2, count( $result['data'] ) );
		$this->assertEquals( 'product', $result['data'][0]['type'] );
		$this->assertEquals( 4, count( $result['data'][0]['attributes'] ) );
		$this->assertEquals( 6, count( $result['data'][0]['attributes']['text'] ) );
		$this->assertEquals( 4, count( $result['data'][0]['attributes']['product/property'] ) );
		$this->assertEquals( 5, count( $result['data'][0]['relationships']['product']['data'] ) );
		$this->assertEquals( 12, count( $result['included'] ) );

		$this->assertArrayNotHasKey( 'errors', $result );
	}
}