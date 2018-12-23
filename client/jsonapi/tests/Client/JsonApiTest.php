<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client;


class JsonApiTest extends \PHPUnit\Framework\TestCase
{
	public function testCreate()
	{
		$context = \TestHelperJapi::getContext();

		$client = \Aimeos\Client\JsonApi::create( $context, 'product' );
		$this->assertInstanceOf( \Aimeos\Client\JsonApi\Iface::class, $client );
	}


	public function testCreateEmpty()
	{
		$context = \TestHelperJapi::getContext();

		$client = \Aimeos\Client\JsonApi::create( $context, '' );
		$this->assertInstanceOf( \Aimeos\Client\JsonApi\Iface::class, $client );
	}


	public function testCreateInvalidPath()
	{
		$context = \TestHelperJapi::getContext();

		$this->setExpectedException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi::create( $context, '%^' );
	}


	public function testCreateInvalidName()
	{
		$context = \TestHelperJapi::getContext();

		$this->setExpectedException( \Aimeos\Client\JsonApi\Exception::class );
		\Aimeos\Client\JsonApi::create( $context, '', '%^' );
	}


	public function testClear()
	{
		$cache = \Aimeos\Client\JsonApi::cache( true );

		$context = \TestHelperJapi::getContext();

		$client1 = \Aimeos\Client\JsonApi::create( $context, 'product' );
		\Aimeos\Client\JsonApi::clear();
		$client2 = \Aimeos\Client\JsonApi::create( $context, 'product' );

		\Aimeos\Client\JsonApi::cache( $cache );

		$this->assertNotSame( $client1, $client2 );
	}


	public function testClearSite()
	{
		$cache = \Aimeos\Client\JsonApi::cache( true );

		$context = \TestHelperJapi::getContext();

		$cntlA1 = \Aimeos\Client\JsonApi::create( $context, 'product' );
		$cntlB1 = \Aimeos\Client\JsonApi::create( $context, 'attribute' );

		\Aimeos\Client\JsonApi::clear( (string) $context );

		$cntlA2 = \Aimeos\Client\JsonApi::create( $context, 'product' );
		$cntlB2 = \Aimeos\Client\JsonApi::create( $context, 'attribute' );

		\Aimeos\Client\JsonApi::cache( $cache );

		$this->assertNotSame( $cntlA1, $cntlA2 );
		$this->assertNotSame( $cntlB1, $cntlB2 );
	}


	public function testClearSpecific()
	{
		$cache = \Aimeos\Client\JsonApi::cache( true );

		$context = \TestHelperJapi::getContext();

		$cntlA1 = \Aimeos\Client\JsonApi::create( $context, 'product' );
		$cntlB1 = \Aimeos\Client\JsonApi::create( $context, 'attribute' );

		\Aimeos\Client\JsonApi::clear( (string) $context, 'product' );

		$cntlA2 = \Aimeos\Client\JsonApi::create( $context, 'product' );
		$cntlB2 = \Aimeos\Client\JsonApi::create( $context, 'attribute' );

		\Aimeos\Client\JsonApi::cache( $cache );

		$this->assertNotSame( $cntlA1, $cntlA2 );
		$this->assertSame( $cntlB1, $cntlB2 );
	}

}