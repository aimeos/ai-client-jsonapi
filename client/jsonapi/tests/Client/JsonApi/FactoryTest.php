<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi;


class FactoryTest extends \PHPUnit_Framework_TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$client = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/detail' );
		$this->assertInstanceOf( '\\Aimeos\\Client\\JsonApi\\Common\\Iface', $client );
	}


	public function testCreateSubClient()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$client = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/filter/tree' );
		$this->assertInstanceOf( '\\Aimeos\\Client\\JsonApi\\Common\\Iface', $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$client = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, '' );
		$this->assertInstanceOf( '\\Aimeos\\Client\\JsonApi\\Common\\Iface', $client );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$this->setExpectedException( '\\Aimeos\\Client\\JsonApi\\Exception' );
		\Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$this->setExpectedException( '\\Aimeos\\Client\\JsonApi\\Exception' );
		\Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, '', '%^' );
	}


	public function testClear()
	{
		$cache = \Aimeos\Client\JsonApi\Factory::setCache( true );

		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$client1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/detail' );
		\Aimeos\Client\JsonApi\Factory::clear();
		$client2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/detail' );

		\Aimeos\Client\JsonApi\Factory::setCache( $cache );

		$this->assertNotSame( $client1, $client2 );
	}


	public function testClearSite()
	{
		$cache = \Aimeos\Client\JsonApi\Factory::setCache( true );

		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$cntlA1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/detail' );
		$cntlB1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/list' );
		\Aimeos\Client\JsonApi\Factory::clear( (string) $context );

		$cntlA2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/detail' );
		$cntlB2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/list' );

		\Aimeos\Client\JsonApi\Factory::setCache( $cache );

		$this->assertNotSame( $cntlA1, $cntlA2 );
		$this->assertNotSame( $cntlB1, $cntlB2 );
	}


	public function testClearSpecific()
	{
		$cache = \Aimeos\Client\JsonApi\Factory::setCache( true );

		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getJsonadmPaths();

		$cntlA1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/detail' );
		$cntlB1 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/list' );

		\Aimeos\Client\JsonApi\Factory::clear( (string) $context, 'catalog/detail' );

		$cntlA2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/detail' );
		$cntlB2 = \Aimeos\Client\JsonApi\Factory::createClient( $context, $templatePaths, 'catalog/list' );

		\Aimeos\Client\JsonApi\Factory::setCache( $cache );

		$this->assertNotSame( $cntlA1, $cntlA2 );
		$this->assertSame( $cntlB1, $cntlB2 );
	}

}