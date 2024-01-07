<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2024
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi\Basket;


/**
 * Base class for JSON API basket clients
 *
 * @package Client
 * @subpackage JsonApi
 */
class Base extends \Aimeos\Client\JsonApi\Base
{
	/**
	 * Clears the basket cache shared between HTML and JSON clients
	 */
	protected function clearCache()
	{
		$session = $this->context()->session();

		foreach( $session->get( 'aimeos/basket/cache', [] ) as $key => $value ) {
			$session->set( $key, null );
		}
	}
}
