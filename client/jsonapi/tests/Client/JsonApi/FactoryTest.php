<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi;


class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::getContext();

		$client = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'product' );
		$this->assertInstanceOf( '\Aimeos\Client\JsonApi\Iface', $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::getContext();

		$client = \Aimeos\Client\JsonApi\Factory::createClient( $context, '' );
		$this->assertInstanceOf( '\Aimeos\Client\JsonApi\Iface', $client );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::getContext();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Factory::createClient( $context, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::getContext();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Factory::createClient( $context, '', '%^' );
	}


	public function testClear()
	{
		$cache = \Aimeos\Client\JsonApi\Factory::setCache( true );

		$context = \TestHelperJapi::getContext();

		$client1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'product' );
		\Aimeos\Client\JsonApi\Factory::clear();
		$client2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'product' );

		\Aimeos\Client\JsonApi\Factory::setCache( $cache );

		$this->assertNotSame( $client1, $client2 );
	}


	public function testClearSite()
	{
		$cache = \Aimeos\Client\JsonApi\Factory::setCache( true );

		$context = \TestHelperJapi::getContext();

		$cntlA1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'product' );
		$cntlB1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'attribute' );
		\Aimeos\Client\JsonApi\Factory::clear( (string) $context );

		$cntlA2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'product' );
		$cntlB2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'attribute' );

		\Aimeos\Client\JsonApi\Factory::setCache( $cache );

		$this->assertNotSame( $cntlA1, $cntlA2 );
		$this->assertNotSame( $cntlB1, $cntlB2 );
	}


	public function testClearSpecific()
	{
		$cache = \Aimeos\Client\JsonApi\Factory::setCache( true );

		$context = \TestHelperJapi::getContext();

		$cntlA1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'product' );
		$cntlB1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'attribute' );

		\Aimeos\Client\JsonApi\Factory::clear( (string) $context, 'product' );

		$cntlA2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'product' );
		$cntlB2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, 'attribute' );

		\Aimeos\Client\JsonApi\Factory::setCache( $cache );

		$this->assertNotSame( $cntlA1, $cntlA2 );
		$this->assertSame( $cntlB1, $cntlB2 );
	}

}