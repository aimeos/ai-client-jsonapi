<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Common\Factory;


/**
 * Common methods for all JSON API factories
 *
 * @package Client
 * @subpackage JsonApi
 */
class Base
{
	private static $objects = [];


	/**
	 * Injects a client object
	 *
	 * The object is returned via create() if an instance of the class
	 * with the name name is requested.
	 *
	 * @param string $classname Full name of the class for which the object should be returned
	 * @param \Aimeos\Client\JsonApi\Iface|null $client JSON API client object
	 */
	public static function injectClient( string $classname, \Aimeos\Client\JsonApi\Iface $client = null )
	{
		self::$objects[$classname] = $client;
	}


	/**
	 * Adds the decorators to the JSON API client object
	 *
	 * @param \Aimeos\Client\JsonApi\Common\Iface $client Client object
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context instance with necessary objects
	 * @param string $path Name of the client, e.g "product"
	 * @return \Aimeos\Client\JsonApi\Iface Client object
	 */
	protected static function addClientDecorators( \Aimeos\Client\JsonApi\Iface $client,
		\Aimeos\MShop\Context\Item\Iface $context, string $path ) : \Aimeos\Client\JsonApi\Iface
	{
		$config = $context->getConfig();

		/** client/jsonapi/common/decorators/default
		 * Configures the list of decorators applied to all JSON API clients
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to configure a list of decorator names that should
		 * be wrapped around the original instance of all created clients:
		 *
		 *  client/jsonapi/common/decorators/default = array( 'decorator1', 'decorator2' )
		 *
		 * This would wrap the decorators named "decorator1" and "decorator2" around
		 * all client instances in that order. The decorator classes would be
		 * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" and
		 * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator2".
		 *
		 * @param array List of decorator names
		 * @since 2015.12
		 * @category Developer
		 */
		$decorators = $config->get( 'client/jsonapi/common/decorators/default', [] );

		if( $path !== null && is_string( $path ) )
		{
			$dpath = trim( $path, '/' );
			$dpath = ( $dpath !== '' ? $dpath . '/' : $dpath );

			$excludes = $config->get( 'client/jsonapi/' . $dpath . 'decorators/excludes', [] );
			$localClass = str_replace( '/', '\\', ucwords( $path, '/' ) );

			foreach( $decorators as $key => $name )
			{
				if( in_array( $name, $excludes ) ) {
					unset( $decorators[$key] );
				}
			}

			$classprefix = '\\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
			$decorators = $config->get( 'client/jsonapi/' . $dpath . 'decorators/global', [] );
			$client = self::addDecorators( $client, $decorators, $classprefix, $context, $path );

			if( !empty( $path ) )
			{
				$classprefix = '\\Aimeos\\Client\\JsonApi\\' . ucfirst( $localClass ) . '\\Decorator\\';
				$decorators = $config->get( 'client/jsonapi/' . $dpath . 'decorators/local', [] );
				$client = self::addDecorators( $client, $decorators, $classprefix, $context, $path );
			}
		}
		else
		{
			$classprefix = '\\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
			$client = self::addDecorators( $client, $decorators, $classprefix, $context, $path );
		}

		return $client;
	}


	/**
	 * Adds the decorators to the client object
	 *
	 * @param \Aimeos\Client\JsonApi\Iface $client Client object
	 * @param array $decorators List of decorator names
	 * @param string $classprefix Decorator class prefix, e.g. "\Aimeos\Client\JsonApi\Product\Decorator\"
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context instance with necessary objects
	 * @param string $path Name of the client, e.g "product"
	 * @return \Aimeos\Client\JsonApi\Iface Client object
	 */
	protected static function addDecorators( \Aimeos\Client\JsonApi\Iface $client, array $decorators, string $classprefix,
			\Aimeos\MShop\Context\Item\Iface $context, string $path ) : \Aimeos\Client\JsonApi\Iface
	{
		foreach( $decorators as $name )
		{
			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? $classprefix . $name : '<not a string>';
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid class name "%1$s"', $classname ), 404 );
			}

			$classname = $classprefix . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Class "%1$s" not found', $classname ), 404 );
			}

			$client = new $classname( $client, $context, $path );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Client\\JsonApi\\Common\\Decorator\\Iface', $client );
		}

		return $client;
	}


	/**
	 * Creates a new client object
	 *
	 * @param string $classname Name of the client class
	 * @param string $interface Name of the client interface
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param string $path Name of the client, e.g "product"
	 * @return \Aimeos\Client\JsonApi\Iface Client object
	 */
	protected static function createClient( string $classname, string $interface, \Aimeos\MShop\Context\Item\Iface $context, string $path ) : \Aimeos\Client\JsonApi\Iface
	{
		if( isset( self::$objects[$classname] ) ) {
			return self::$objects[$classname];
		}

		if( class_exists( $classname ) === false ) {
			throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Class "%1$s" not found', $classname ), 404 );
		}

		$client = new $classname( $context, $path );

		\Aimeos\MW\Common\Base::checkClass( $interface, $client );

		return $client;
	}
}
