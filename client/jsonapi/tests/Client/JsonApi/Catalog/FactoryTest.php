<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Catalog;


class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::context();

		$client = \Aimeos\Client\JsonApi\Catalog\Factory::create( $context, 'attribute' );
		$this->assertInstanceOf( \Aimeos\Client\JsonApi\Iface::class, $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::context();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Catalog\Factory::create( $context, '' );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::context();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Catalog\Factory::create( $context, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::context();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Catalog\Factory::create( $context, 'catalog', '%^' );
	}
}
