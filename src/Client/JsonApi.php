<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client;


/**
 * Factory which can create all JSON API clients
 *
 * @package Client
 * @subpackage JsonApi
 */
class JsonApi
{
	/** client/jsonapi/name
	 * Class name of the used JSON API client implementation
	 *
	 * Each default JSON API client can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the client factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Client\JsonApi\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Client\JsonApi\Mycntl
	 *
	 * then you have to set the this configuration option:
	 *
	 *  client/jsonapi/name = Mycntl
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyCntl"!
	 *
	 * @param string Last part of the class name
	 * @since 2015.12
	 * @category Developer
	 */

	/** client/jsonapi/decorators/excludes
	 * Excludes decorators added by the "common" option from the JSON API clients
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "client/jsonapi/common/decorators/default" before they are wrapped
	 * around the Jsonadm client.
	 *
	 *  client/jsonapi/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Client\JsonApi\Common\Decorator\*") added via
	 * "client/jsonapi/common/decorators/default" for the JSON API client.
	 *
	 * @param array List of decorator names
	 * @since 2016.01
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/decorators/global
	 * @see client/jsonapi/decorators/local
	 */

	/** client/jsonapi/decorators/global
	 * Adds a list of globally available decorators only to the Jsonadm client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Client\Jsonadm\Common\Decorator\*") around the Jsonadm
	 * client.
	 *
	 *  client/jsonapi/product/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Client\Jsonadm\Common\Decorator\Decorator1" only to the
	 * "product" Jsonadm client.
	 *
	 * @param array List of decorator names
	 * @since 2016.01
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/decorators/excludes
	 * @see client/jsonapi/decorators/local
	 */

	/** client/jsonapi/decorators/local
	 * Adds a list of local decorators only to the Jsonadm client
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Client\Jsonadm\Product\Decorator\*") around the Jsonadm
	 * client.
	 *
	 *  client/jsonapi/product/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Client\Jsonadm\Product\Decorator\Decorator2" only to the
	 * "product" Jsonadm client.
	 *
	 * @param array List of decorator names
	 * @since 2016.01
	 * @category Developer
	 * @see client/jsonapi/common/decorators/default
	 * @see client/jsonapi/decorators/excludes
	 * @see client/jsonapi/decorators/global
	 */


	private static $objects = [];


	/**
	 * Creates the required client specified by the given path of client names
	 *
	 * Clients are created by providing only the domain name, e.g. "product"
	 *  for the \Aimeos\Client\JsonApi\Product\Standard or a path of names to
	 * retrieve a specific sub-client, e.g. "product/type" for the
	 * \Aimeos\Client\JsonApi\Product\Type\Standard client.
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object required by clients
	 * @param string $path Name of the client separated by slashes, e.g "order/product"
	 * @param string|null $name Name of the client implementation ("Standard" if null)
	 * @return \Aimeos\Client\JsonApi\Iface JSON client instance
	 * @throws \Aimeos\Client\JsonApi\Exception If the given path is invalid
	 */
	public static function create( \Aimeos\MShop\ContextIface $context,
		string $path, string $name = null ) : \Aimeos\Client\JsonApi\Iface
	{
		empty( $path = trim( $path, '/' ) ) ?: $path .= '/';

		if( $name === null ) {
			$name = $context->config()->get( 'client/jsonapi/' . $path . 'name', 'Standard' );
		}

		$interface = 'Aimeos\\Client\\JsonApi\\Iface';
		$classname = 'Aimeos\\Client\\JsonApi\\' . str_replace( '/', '\\', ucwords( $path, '/' ) ) . $name;

		if( class_exists( $classname ) === false ) {
			throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Class "%1$s" not found', $classname, 404 ) );
		}

		$client = self::createComponent( $context, $classname, $interface, $path );

		return $client->setView( $context->view() );
	}


	/**
	 * Injects a client object
	 *
	 * The object is returned via create() if an instance of the class
	 * with the name name is requested.
	 *
	 * @param string $classname Full name of the class for which the object should be returned
	 * @param \Aimeos\Client\JsonApi\Iface|null $client JSON API client object
	 */
	public static function inject( string $classname, \Aimeos\Client\JsonApi\Iface $client = null )
	{
		self::$objects['\\' . ltrim( $classname, '\\' )] = $client;
	}


	/**
	 * Adds the decorators to the JSON API client object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context instance with necessary objects
	 * @param \Aimeos\Client\JsonApi\Iface $client Client object
	 * @param string $path Name of the client, e.g "product"
	 * @return \Aimeos\Client\JsonApi\Iface Client object
	 */
	protected static function addComponentDecorators( \Aimeos\MShop\ContextIface $context,
		\Aimeos\Client\JsonApi\Iface $client, string $path ) : \Aimeos\Client\JsonApi\Iface
	{
		$localClass = str_replace( '/', '\\', ucwords( $path, '/' ) );
		$config = $context->config();

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
		$excludes = $config->get( 'client/jsonapi/' . $path . 'decorators/excludes', [] );

		foreach( $decorators as $key => $name )
		{
			if( in_array( $name, $excludes ) ) {
				unset( $decorators[$key] );
			}
		}

		$classprefix = '\\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$client = self::addDecorators( $context, $client, $path, $decorators, $classprefix );

		$classprefix = '\\Aimeos\\Client\\JsonApi\\Common\\Decorator\\';
		$decorators = $config->get( 'client/jsonapi/' . $path . 'decorators/global', [] );
		$client = self::addDecorators( $context, $client, $path, $decorators, $classprefix );

		$classprefix = '\\Aimeos\\Client\\JsonApi\\' . $localClass . 'Decorator\\';
		$decorators = $config->get( 'client/jsonapi/' . $path . 'decorators/local', [] );
		$client = self::addDecorators( $context, $client, $path, $decorators, $classprefix );

		return $client;
	}


	/**
	 * Adds the decorators to the client object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context instance with necessary objects
	 * @param \Aimeos\Client\JsonApi\Iface $client Client object
	 * @param string $path Name of the component, e.g "product"
	 * @param array $decorators List of decorator names
	 * @param string $classprefix Decorator class prefix, e.g. "\Aimeos\Client\JsonApi\Product\Decorator\"
	 * @return \Aimeos\Client\JsonApi\Iface Client object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected static function addDecorators( \Aimeos\MShop\ContextIface $context, \Aimeos\Client\JsonApi\Iface $client,
		string $path, array $decorators, string $classprefix ) : \Aimeos\Client\JsonApi\Iface
	{
		$interface = \Aimeos\Client\JsonApi\Common\Decorator\Iface::class;

		foreach( $decorators as $name )
		{
			if( ctype_alnum( $name ) === false ) {
				throw new \LogicException( sprintf( 'Invalid class name "%1$s"', $name ), 400 );
			}

			$client = \Aimeos\Utils::create( $classprefix . $name, [$client, $context, $path], $interface );
		}

		return $client;
	}


	/**
	 * Creates a new client object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param string $classname Name of the client class
	 * @param string $interface Name of the client interface
	 * @param string $path Name of the client separated by slashes, e.g "order/product"
	 * @return \Aimeos\Client\JsonApi\Iface Client object
	 */
	protected static function createComponent( \Aimeos\MShop\ContextIface $context,
		string $classname, string $interface, string $path ) : \Aimeos\Client\JsonApi\Iface
	{
		if( isset( self::$objects[$classname] ) ) {
			return self::$objects[$classname];
		}

		$client = \Aimeos\Utils::create( $classname, [$context], $interface );

		return self::addComponentDecorators( $context, $client, $path );
	}
}
