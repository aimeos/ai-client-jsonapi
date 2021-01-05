<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
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
		$session = $this->getContext()->getSession();

		foreach( $session->get( 'aimeos/basket/cache', [] ) as $key => $value ) {
			$session->set( $key, null );
		}
	}


	/**
	 * Translates the plugin error codes to human readable error strings.
	 *
	 * @param array $codes Associative list of scope and object as key and error code as value
	 * @return array List of translated error messages
	 */
	protected function translatePluginErrorCodes( array $codes ) : array
	{
		$errors = [];
		$i18n = $this->getContext()->getI18n();

		foreach( $codes as $scope => $list )
		{
			foreach( $list as $object => $errcode )
			{
				$key = $scope . ( !in_array( $scope, ['coupon', 'product'] ) ? '.' . $object : '' ) . '.' . $errcode;
				$errors[] = sprintf( $i18n->dt( 'mshop/code', $key ), $object );
			}
		}

		return $errors;
	}
}
