<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Common\Factory;


/**
 * Generic interface for all JSON API client factories
 *
 * @package Client
 * @subpackage JsonApi
 * @deprecated 2023.01
 */
interface Iface
{
	/**
	 * Creates a new client based on the name
	 *
	 * @param \Aimeos\MShop\ContextIface $context MShop context object
	 * @param string $path Name of the client separated by slashes, e.g "product"
	 * @param string|null $name Name of the client implementation ("Standard" if null)
	 * @return \Aimeos\Client\JsonApi\Iface Client Interface
	 */
	public static function create( \Aimeos\MShop\ContextIface $context, string $path, string $name = null ) : \Aimeos\Client\JsonApi\Iface;
}
