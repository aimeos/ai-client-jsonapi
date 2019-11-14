<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 * @package MW
 * @subpackage View
 */


namespace Aimeos\MW\View\Helper\Included;


/**
 * View helper class for generating "included" data used by JSON:API
 *
 * @package MW
 * @subpackage View
 */
interface Iface extends \Aimeos\MW\View\Helper\Iface
{
	/**
	 * Returns the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 * @return array Multi-dimensional array of included data
	 */
	public function transform( \Aimeos\MShop\Common\Item\Iface $item, array $fields );
}
