<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Customer;


/**
 * Factory for customer JSON API client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Factory
	extends \Aimeos\Client\JsonApi\Common\Factory\Base
	implements \Aimeos\Client\JsonApi\Common\Factory\Iface
{
	/**
	 * Creates a customer client object.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Shop context instance with necessary objects
	 * @param string $path Name of the client separated by slashes, e.g "product"
	 * @param string|null $name Client name (default: "Standard")
	 * @return \Aimeos\Client\JsonApi\Iface JSON API client
	 * @throws \Aimeos\Client\JsonApi\Exception If requested client implementation couldn't be found or initialisation fails
	 */
	public static function createClient( \Aimeos\MShop\Context\Item\Iface $context, $path, $name = null )
	{
		if( ctype_alnum( $path ) === false )
		{
			$path = ( is_string( $path ) ? $path : '<not a string>' );
			throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid client "%1$s"', $path ), 400 );
		}

		/** client/jsonapi/customer/name
		 * Class name of the used customer client implementation
		 *
		 * Each default JSON API client can be replace by an alternative imlementation.
		 * To use this implementation, you have to set the last part of the class
		 * name as configuration value so the client factory knows which class it
		 * has to instantiate.
		 *
		 * For example, if the name of the default class is
		 *
		 *  \Aimeos\Client\JsonApi\Customer\Standard
		 *
		 * and you want to replace it with your own version named
		 *
		 *  \Aimeos\Client\JsonApi\Customer\Mycustomer
		 *
		 * then you have to set the this configuration option:
		 *
		 *  client/jsonapi/customer/name = Mycustomer
		 *
		 * The value is the last part of your own class name and it's case sensitive,
		 * so take care that the configuration value is exactly named like the last
		 * part of the class name.
		 *
		 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
		 * characters are possible! You should always start the last part of the class
		 * name with an upper case character and continue only with lower case characters
		 * or numbers. Avoid chamel case names like "MyCustomer"!
		 *
		 * @param string Last part of the class name
		 * @since 2017.04
		 * @category Developer
		 */
		if( $name === null ) {
			$name = $context->getConfig()->get( 'client/jsonapi/customer/name', 'Standard' );
		}

		if( ctype_alnum( $name ) === false )
		{
			$classname = is_string( $name ) ? '\\Aimeos\\Client\\JsonApi\\Customer\\' . $name : '<not a string>';
			throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		$iface = '\\Aimeos\\Client\\JsonApi\\Iface';
		$classname = '\\Aimeos\\Client\\JsonApi\\Customer\\' . $name;

		$client = self::createClientBase( $classname, $iface, $context, $path );


		/** client/jsonapi/customer/decorators/excludes
		 * Excludes decorators added by the "common" option from the JSON API clients
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "client/jsonapi/common/decorators/default" before they are wrapped
		 * around the JsonApi client.
		 *
		 *  client/jsonapi/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Client\JsonApi\Common\Decorator\*") added via
		 * "client/jsonapi/common/decorators/default" for the JSON API client.
		 *
		 * @param array List of decorator names
		 * @since 2017.07
		 * @category Developer
		 * @see client/jsonapi/common/decorators/default
		 * @see client/jsonapi/customer/decorators/global
		 * @see client/jsonapi/customer/decorators/local
		 */

		/** client/jsonapi/customer/decorators/global
		 * Adds a list of globally available decorators only to the JsonApi client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\Client\JsonApi\Common\Decorator\*") around the JsonApi
		 * client.
		 *
		 *  client/jsonapi/customer/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Client\JsonApi\Common\Decorator\Decorator1" only to the
		 * "customer" JsonApi client.
		 *
		 * @param array List of decorator names
		 * @since 2017.07
		 * @category Developer
		 * @see client/jsonapi/common/decorators/default
		 * @see client/jsonapi/customer/decorators/excludes
		 * @see client/jsonapi/customer/decorators/local
		 */

		/** client/jsonapi/customer/decorators/local
		 * Adds a list of local decorators only to the JsonApi client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\Client\JsonApi\Customer\Decorator\*") around the JsonApi
		 * client.
		 *
		 *  client/jsonapi/customer/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Client\JsonApi\Customer\Decorator\Decorator2" only to the
		 * "customer" JsonApi client.
		 *
		 * @param array List of decorator names
		 * @since 2017.07
		 * @category Developer
		 * @see client/jsonapi/common/decorators/default
		 * @see client/jsonapi/customer/decorators/excludes
		 * @see client/jsonapi/customer/decorators/global
		 */

		$client = self::addClientDecorators( $client, $context, $path );

		return $client->setView( $context->getView() );
	}

}

