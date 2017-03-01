<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 */


namespace Aimeos\Client\JsonApi\Common\Factory;


class BaseTest extends \PHPUnit_Framework_TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->context = \TestHelperJapi::getContext();
		$this->view = $this->context->getView();

		$this->client = new \Aimeos\Client\JsonApi\Product\Standard( $this->context, $this->view, [], '' );

		$this->object = $this->getMockBuilder( '\Aimeos\Client\JsonApi\Common\Factory\Base' )
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

		$params = [$this->client, $this->context, $this->view, [], 'product'];
		$result = $this->access( 'addClientDecorators' )->invokeArgs( $this->object, $params );

		$this->assertInstanceOf( '\Aimeos\\Client\\JsonApi\\Iface', $result );
	}


	public function testAddDecorators()
	{
		$prefix = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$params = [$this->client, ['Example'], $prefix, $this->context, $this->view, [], ''];

		$result = $this->access( 'addDecorators' )->invokeArgs( $this->object, $params );

		$this->assertInstanceOf( '\Aimeos\\Client\\JsonApi\\Iface', $result );
	}


	public function testAddDecoratorsInvalidClass()
	{
		$prefix = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$params = [$this->client, ['Test'], $prefix, $this->context, $this->view, [], ''];

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		$this->access( 'addDecorators' )->invokeArgs( $this->object, $params );
	}


	public function testAddDecoratorsInvalidName()
	{
		$prefix = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$params = [$this->client, [''], $prefix, $this->context, $this->view, [], ''];

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		$this->access( 'addDecorators' )->invokeArgs( $this->object, $params );
	}


	public function testCreateClientBase()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Iface';
		$class = '\Aimeos\\Client\\JsonApi\\Product\\Standard';
		$params = [$class, $iface, $this->context, $this->view, [], ''];

		$result = $this->access( 'createClientBase' )->invokeArgs( $this->object, $params );

		$this->assertInstanceOf( '\Aimeos\\Client\\JsonApi\\Iface', $result );
	}


	public function testCreateClientBaseCache()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Iface';
		$params = ['test', $iface, $this->context, $this->view, [], ''];

		$this->object->injectClient( 'test', $this->client );
		$result = $this->access( 'createClientBase' )->invokeArgs( $this->object, $params );

		$this->assertSame( $this->client, $result );
	}


	public function testCreateClientBaseInvalidClass()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Iface';
		$params = ['invalid', $iface, $this->context, $this->view, [], ''];

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		$this->access( 'createClientBase' )->invokeArgs( $this->object, $params );
	}


	public function testCreateClientBaseInvalidIface()
	{
		$iface = '\Aimeos\\Client\\JsonApi\\Common\\Decorator\\Iface';
		$class = '\Aimeos\\Client\\JsonApi\\Product\\Standard';
		$params = [$class, $iface, $this->context, $this->view, [], ''];

		$this->setExpectedException( '\Aimeos\Client\JsonApi\Exception' );
		$this->access( 'createClientBase' )->invokeArgs( $this->object, $params );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( '\Aimeos\Client\JsonApi\Common\Factory\Base' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}