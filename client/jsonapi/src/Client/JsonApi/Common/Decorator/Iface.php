<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Common\Decorator;


/**
 * Decorator interface for JSON API client
 *
 * @package Client
 * @subpackage JsonApi
 */
interface Iface
	extends \Aimeos\Client\JsonApi\Iface
{
	/**
	 * Initializes a new client decorator object
	 *
	 * @param \Aimeos\Client\JsonApi\Iface $client Client object
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object with required objects
	 * @param string $path Name of the client separated by slashes, e.g "product/stock"
	 */
	public function __construct( \Aimeos\Client\JsonApi\Iface $client,
		\Aimeos\MShop\Context\Item\Iface $context, string $path );
}
