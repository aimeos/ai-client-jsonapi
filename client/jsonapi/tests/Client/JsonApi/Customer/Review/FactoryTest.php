<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020
 */


namespace Aimeos\Client\JsonApi\Customer\Review;


class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::getContext();

		$client = \Aimeos\Client\JsonApi\Customer\Review\Factory::create( $context, 'customer/review' );
		$this->assertInstanceOf( \Aimeos\Client\JsonApi\Iface::class, $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::getContext();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Customer\Review\Factory::create( $context, '' );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::getContext();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Customer\Review\Factory::create( $context, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::getContext();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Customer\Review\Factory::create( $context, 'customer/review', '%^' );
	}
}
