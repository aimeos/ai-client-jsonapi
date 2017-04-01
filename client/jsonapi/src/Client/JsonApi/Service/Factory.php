<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Service;


/**
 * Factory for service JSON API client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Factory
	extends \Aimeos\Client\JsonApi\Common\Factory\Base
	implements \Aimeos\Client\JsonApi\Common\Factory\Iface
{
	/**
	 * Creates a service client object.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Shop context instance with necessary objects
	 * @param array $templatePaths List of file system paths where the templates are stored
	 * @param string $path Name of the client separated by slashes, e.g "service"
	 * @param string|null $name Client name (default: "Standard")
	 * @return \Aimeos\Client\JsonApi\Iface JSON API client
	 * @throws \Aimeos\Client\JsonApi\Exception If requested client implementation couldn't be found or initialisation fails
	 */
	public static function createClient( \Aimeos\MShop\Context\Item\Iface $context, array $templatePaths, $path, $name = null )
	{
		if( ctype_alnum( $path ) === false )
		{
			$path = ( is_string( $path ) ? $path : '<not a string>' );
			throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid client "%1$s"', $path ), 400 );
		}

		/** client/jsonapi/service/name
		 * Class name of the used service client implementation
		 *
		 * Each default JSON API client can be replace by an alternative imlementation.
		 * To use this implementation, you have to set the last part of the class
		 * name as configuration value so the client factory knows which class it
		 * has to instantiate.
		 *
		 * For example, if the name of the default class is
		 *
		 *  \Aimeos\Client\JsonApi\Service\Standard
		 *
		 * and you want to replace it with your own version named
		 *
		 *  \Aimeos\Client\JsonApi\Service\Myservice
		 *
		 * then you have to set the this configuration option:
		 *
		 *  client/jsonapi/service/name = Myservice
		 *
		 * The value is the last part of your own class name and it's case sensitive,
		 * so take care that the configuration value is exactly named like the last
		 * part of the class name.
		 *
		 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
		 * characters are possible! You should always start the last part of the class
		 * name with an upper case character and continue only with lower case characters
		 * or numbers. Avoid chamel case names like "MyService"!
		 *
		 * @param string Last part of the class name
		 * @since 2017.03
		 * @category Developer
		 */
		if( $name === null ) {
			$name = $context->getConfig()->get( 'client/jsonapi/service/name', 'Standard' );
		}

		if( ctype_alnum( $name ) === false )
		{
			$classname = is_string( $name ) ? '\\Aimeos\\Client\\JsonApi\\Service\\' . $name : '<not a string>';
			throw new \Aimeos\Client\JsonApi\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		$view = $context->getView();
		$iface = '\\Aimeos\\Client\\JsonApi\\Iface';
		$classname = '\\Aimeos\\Client\\JsonApi\\Service\\' . $name;

		$client = self::createClientBase( $classname, $iface, $context, $view, $templatePaths, $path );

		return self::addClientDecorators( $client, $context, $view, $templatePaths, $path );
	}

}

