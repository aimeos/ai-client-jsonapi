<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\MW\View\Helper\Jincluded;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$this->object = new \Aimeos\MW\View\Helper\Jincluded\Standard( new \Aimeos\MW\View\Standard() );
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testTransformCatalog()
	{
		$manager = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'catalog' );
		$tree = $manager->getTree( $manager->find( 'group' )->getId(), ['media', 'text'] );

		$this->assertEquals( 13, map( $this->object->transform( $tree, [] ) )->flat( 1 )->count() );
	}


	public function testTransformCustomer()
	{
		$domains = ['customer/address', 'customer/property'];
		$item = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'customer' )->find( 'test@example.com', $domains );

		$this->assertEquals( 2, map( $this->object->transform( $item, [] ) )->flat( 1 )->count() );
	}


	public function testTransformProduct()
	{
		$domains = ['attribute', 'catalog', 'media', 'price', 'product', 'product/property', 'supplier', 'stock', 'text'];
		$item = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'product' )->find( 'CNE', $domains );

		$this->assertGreaterThanOrEqual( 77, map( $this->object->transform( $item, [] ) )->flat( 1 )->count() );
	}


	public function testTransformProducts()
	{
		$domains = ['attribute', 'catalog', 'media', 'price', 'product', 'product/property', 'supplier', 'stock', 'text'];
		$item = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'product' )->find( 'CNE', $domains );

		$this->assertGreaterThanOrEqual( 77, map( $this->object->transform( [$item], [] ) )->flat( 1 )->count() );
	}
}
