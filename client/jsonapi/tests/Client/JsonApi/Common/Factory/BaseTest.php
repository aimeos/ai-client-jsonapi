<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Client\JsonApi\Common\Factory;


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $client;
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->view();

		$this->client = new \Aimeos\Client\JsonApi\Product\Standard( $this->context, '' );

		$this->object = $this->getMockBuilder( \Aimeos\Client\JsonApi\Common\Factory\Base::class )
			->getMockForAbstractClass();
	}


	public function testinjectClient()
	{
		$this->object->injectClient( 'test', $this->client );
	}


	public function testAddClientDecorators()
	{
		$config = $this->context->getConfig();
		$config->set( 'client/jsonapi/common/decorators/default', ['Test'] );
		$config->set( 'client/jsonapi/product/decorators/excludes', ['Test'] );

		$params = [$this->client, $this->context, 'product'];
		$result = $this->access( 'addClientDecorators' )->invokeArgs( $this->object, $params );

		$this->assertInstanceOf( '\Aimeos\\Client\\JsonApi\\Iface', $result );
	}


	public function testAddDecorators()
	{
		$prefix = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$params = [$this->client, ['Example'], $prefix, $this->context, ''];

		$result = $this->access( 'addDecorators' )->invokeArgs( $this->object, $params );

		$this->assertInstanceOf( '\Aimeos\\Client\\JsonApi\\Iface', $result );
	}


	public function testAddDecoratorsInvalidClass()
	{
		$prefix = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$params = [$this->client, ['Test'], $prefix, $this->context, ''];

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		$this->access( 'addDecorators' )->invokeArgs( $this->object, $params );
	}


	public function testAddDecoratorsInvalidName()
	{
		$prefix = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$params = [$this->client, [''], $prefix, $this->context, ''];

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		$this->access( 'addDecorators' )->invokeArgs( $this->object, $params );
	}


	public function testCreateClientBase()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Iface';
		$class = '\Aimeos\\Client\\JsonApi\\Product\\Standard';
		$params = [$class, $iface, $this->context, ''];

		$result = $this->access( 'createClient' )->invokeArgs( $this->object, $params );

		$this->assertInstanceOf( '\Aimeos\\Client\\JsonApi\\Iface', $result );
	}


	public function testCreateClientBaseCache()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Iface';
		$params = ['test', $iface, $this->context, ''];

		$this->object->injectClient( 'test', $this->client );
		$result = $this->access( 'createClient' )->invokeArgs( $this->object, $params );

		$this->assertSame( $this->client, $result );
	}


	public function testCreateClientBaseInvalidClass()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Iface';
		$params = ['invalid', $iface, $this->context, ''];

		$this->expectException( \Aimeos\Client\JsonApi\Exception::class );
		$this->access( 'createClient' )->invokeArgs( $this->object, $params );
	}


	public function testCreateClientBaseInvalidIface()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\Iface';
		$class = '\Aimeos\\Client\\JsonApi\\Product\\Standard';
		$params = [$class, $iface, $this->context, ''];

		$this->expectException( \Aimeos\MW\Common\Exception::class );
		$this->access( 'createClient' )->invokeArgs( $this->object, $params );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Client\JsonApi\Common\Factory\Base::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
