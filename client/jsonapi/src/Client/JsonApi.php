<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
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
class JsonApi extends \Aimeos\Client\JsonApi\Common\Factory\Base
{
	/**
	 * Creates the required client specified by the given path of client names
	 *
	 * Clients are created by providing only the domain name, e.g. "product"
	 *  for the \Aimeos\Client\JsonApi\Product\Standard or a path of names to
	 * retrieve a specific sub-client, e.g. "product/type" for the
	 * \Aimeos\Client\JsonApi\Product\Type\Standard client.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object required by clients
	 * @param string $path Name of the client separated by slashes, e.g "order/base"
	 * @param string|null $name Name of the client implementation ("Standard" if null)
	 * @return \Aimeos\Client\JsonApi\Iface JSON client instance
	 * @throws \Aimeos\Client\JsonApi\Exception If the given path is invalid
	 */
	public static function create( \Aimeos\MShop\Context\Item\Iface $context, string $path, string $name = null ) : \Aimeos\Client\JsonApi\Iface
	{
		$path = trim( $path, '/' );

		if( empty( $path ) ) {
			return self::createRoot( $context, $path, $name );
		}

		$parts = explode( '/', $path );

		foreach( $parts as $key => $part )
		{
			if( ctype_alnum( $part ) === false )
			{
				$msg = sprintf( 'Invalid client "%1$s"', $path );
				throw new \Aimeos\Client\JsonApi\Exception( $msg, 400 );
			}

			$parts[$key] = ucfirst( $part );
		}

		$factory = '\\Aimeos\\Client\\JsonApi\\' . join( '\\', $parts ) . '\\Factory';

		if( class_exists( $factory ) === true )
		{
			if( ( $client = @call_user_func_array( [$factory, 'create'], [$context, $path, $name] ) ) === false ) {
				throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid factory "%1$s"', $factory ), 400 );
			}
		}
		else
		{
			$client = self::createRoot( $context, $path, $name );
		}

		return $client;
	}


	/**
	 * Creates the top level client
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object required by clients
	 * @param string $path Name of the client separated by slashes, e.g "order/base"
	 * @param string|null $name Name of the JsonApi client (default: "Standard")
	 * @return \Aimeos\Client\JsonApi\Iface JSON client instance
	 * @throws \Aimeos\Client\JsonApi\Exception If the client couldn't be created
	 */
	protected static function createRoot( \Aimeos\MShop\Context\Item\Iface $context, string $path, string $name = null ) : \Aimeos\Client\JsonApi\Iface
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
		if( $name === null ) {
			$name = $context->getConfig()->get( 'client/jsonapi/name', 'Standard' );
		}

		$iface = '\\Aimeos\\Client\\JsonApi\\Iface';
		$classname = '\\Aimeos\\Client\\JsonApi\\' . $name;

		if( ctype_alnum( $name ) === false ) {
			throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid class name "%1$s"', $classname ) );
		}

		$client = self::createClient( $classname, $iface, $context, $path );

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

		$client = self::addClientDecorators( $client, $context, $path );

		return $client->setView( $context->getView() );
	}
}
