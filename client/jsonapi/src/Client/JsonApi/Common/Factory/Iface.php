<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Common\Factory;


/**
 * Generic interface for all JSON API client factories
 *
 * @package Client
 * @subpackage JsonApi
 */
interface Iface
{
	/**
	 * Creates a new client based on the name
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context MShop context object
	 * @param string $path Name of the client separated by slashes, e.g "product"
	 * @param string|null $name Name of the client implementation ("Standard" if null)
	 * @return \Aimeos\Client\JsonApi\Iface Client Interface
	 */
	public static function createClient( \Aimeos\MShop\Context\Item\Iface $context, $path, $name = null );
}
