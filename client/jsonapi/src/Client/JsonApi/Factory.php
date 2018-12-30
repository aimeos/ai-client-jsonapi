<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi;


/**
 * Factory which can create all JSON API clients
 *
 * @package Client
 * @subpackage JsonApi
 * @deprecated Use JsonApi class instead
 */
class Factory
	extends \Aimeos\Client\JsonApi
	implements \Aimeos\Client\JsonApi\Common\Factory\Iface
{
	/**
	 * Creates the required client specified by the given path of client names.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object required by clients
	 * @param string $path Name of the client separated by slashes, e.g "order/base"
	 * @param string|null $name Name of the client implementation ("Standard" if null)
	 * @return \Aimeos\Client\JsonApi\Iface JSON client instance
	 * @throws \Aimeos\Client\JsonApi\Exception If the given path is invalid
	 */
	static public function create( \Aimeos\MShop\Context\Item\Iface $context, $path, $name = null )
	{
		return parent::create( $context, $path, $name );
	}


	/**
	 * Enables or disables caching of class instances.
	 *
	 * @param boolean $value True to enable caching, false to disable it.
	 * @return boolean Previous cache setting
	 */
	static public function setCache( $value )
	{
		return self::cache( $value );
	}
}
