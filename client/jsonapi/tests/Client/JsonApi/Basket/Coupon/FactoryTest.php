<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2020
 */


namespace Aimeos\Client\JsonApi\Basket\Coupon;


class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::getContext();

		$client = \Aimeos\Client\JsonApi\Basket\Coupon\Factory::create( $context, 'basket/coupon' );
		$this->assertInstanceOf( \Aimeos\Client\JsonApi\Iface::class, $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::getContext();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Basket\Coupon\Factory::create( $context, '' );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::getContext();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Basket\Coupon\Factory::create( $context, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::getContext();

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi\Basket\Coupon\Factory::create( $context, 'basket/coupon', '%^' );
	}
}
