<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Service;


class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::context();

		$client = \Aimeos\Client\JsonApi\Service\Factory::create( $context, 'service' );
		$this->assertInstanceOf( \Aimeos\Client\JsonApi\Iface::class, $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::context();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Service\Factory::create( $context, '' );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::context();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Service\Factory::create( $context, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::context();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Service\Factory::create( $context, 'service', '%^' );
	}
}
