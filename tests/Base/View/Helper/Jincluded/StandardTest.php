<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2023
 */


namespace Aimeos\Base\View\Helper\Jincluded;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$this->object = new \Aimeos\Base\View\Helper\Jincluded\Standard( new \Aimeos\Base\View\Standard() );
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testTransformCatalog()
	{
		$manager = \Aimeos\MShop::create( \TestHelper::context(), 'catalog' );
		$tree = $manager->getTree( $manager->find( 'group' )->getId(), ['media', 'text'] );

		$this->assertEquals( 13, map( $this->object->transform( $tree, [] ) )->flat( 1 )->count() );
	}


	public function testTransformCustomer()
	{
		$domains = ['customer/address', 'customer/property'];
		$item = \Aimeos\MShop::create( \TestHelper::context(), 'customer' )->find( 'test@example.com', $domains );

		$this->assertEquals( 2, map( $this->object->transform( $item, [] ) )->flat( 1 )->count() );
	}


	public function testTransformProduct()
	{
		$domains = ['attribute', 'catalog', 'media', 'price', 'product', 'product/property', 'supplier', 'stock', 'text'];
		$item = \Aimeos\MShop::create( \TestHelper::context(), 'product' )->find( 'CNE', $domains );

		$this->assertGreaterThanOrEqual( 74, map( $this->object->transform( $item, [] ) )->flat( 1 )->count() );
	}


	public function testTransformProducts()
	{
		$domains = ['attribute', 'catalog', 'media', 'price', 'product', 'product/property', 'supplier', 'stock', 'text'];
		$item = \Aimeos\MShop::create( \TestHelper::context(), 'product' )->find( 'CNE', $domains );

		$this->assertGreaterThanOrEqual( 74, map( $this->object->transform( [$item], [] ) )->flat( 1 )->count() );
	}
}
