<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi\Stock;


class FactoryTest extends \PHPUnit_Framework_TestCase
{
	public function testCreateClient()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$client = \Aimeos\Client\JsonApi\Stock\Factory::createClient( $context, $templatePaths, 'stock' );
		$this->assertInstanceOf( '\Aimeos\Client\JsonApi\Iface', $client );
	}


	public function testCreateClientEmpty()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Stock\Factory::createClient( $context, $templatePaths, '' );
	}


	public function testCreateClientInvalidPath()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Stock\Factory::createClient( $context, $templatePaths, '%^' );
	}


	public function testCreateClientInvalidName()
	{
		$context = \TestHelperJapi::getContext();
		$templatePaths = \TestHelperJapi::getTemplatePaths();

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		\Aimeos\Client\JsonApi\Stock\Factory::createClient( $context, $templatePaths, 'stock', '%^' );
	}
}