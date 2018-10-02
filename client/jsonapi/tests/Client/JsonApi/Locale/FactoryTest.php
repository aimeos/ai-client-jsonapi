<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Client\JsonApi\Locale;


class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::getContext();

		$client = \Aimeos\Client\JsonApi\Locale\Factory::createClient( $context, 'locale' );
		$this->assertInstanceOf( '\Aimeos\Client\JsonApi\Iface', $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::getContext();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Locale\Factory::createClient( $context, '' );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::getContext();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Locale\Factory::createClient( $context, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::getContext();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Locale\Factory::createClient( $context, 'locale', '%^' );
	}
}