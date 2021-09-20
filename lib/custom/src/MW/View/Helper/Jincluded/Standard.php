<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 * @package MW
 * @subpackage View
 */


namespace Aimeos\MW\View\Helper\Jincluded;


/**
 * View helper class for generating "included" data used by JSON:API
 *
 * @package MW
 * @subpackage View
 */
class Standard extends \Aimeos\MW\View\Helper\Base implements Iface
{
	private $map;


	/**
	 * Returns the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface|\Aimeos\MShop\Common\Item\Iface[] $item Object or objects to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 * @param array $fcn Associative list of resource types as keys and anonymous functions for generating the array entries as values
	 * @return array List of entries to include in the JSON:API response
	 */
	public function transform( $item, array $fields, array $fcn = [] )
	{
		$this->map = [];

		if( is_map( $item ) || is_array( $item ) )
		{
			foreach( $item as $entry ) {
				$this->entry( $entry, $fields, $fcn );
			}
		}
		else
		{
			$this->entry( $item, $fields, $fcn );
		}

		return $this->map;
	}


	/**
	 * Processes a single item to create the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 * @param array $fcn Associative list of resource types as keys and anonymous functions for generating the array entries as values
	 */
	protected function entry( \Aimeos\MShop\Common\Item\Iface $item, array $fields, array $fcn = [] )
	{
		if( $item instanceof \Aimeos\MShop\Common\Item\Tree\Iface )
		{
			foreach( $item->getChildren() as $catItem )
			{
				if( $catItem->isAvailable() ) {
					$this->map( $catItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\AddressRef\Iface )
		{
			foreach( $item->getAddressItems() as $addrItem ) {
				$this->map( $addrItem, $fields, $fcn );
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\ListsRef\Iface )
		{
			foreach( $item->getListItems() as $listItem )
			{
				if( $refItem = $listItem->getRefItem() ) {
					$this->map( $refItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
		{
			foreach( $item->getPropertyItems() as $propItem ) {
				$this->map( $propItem, $fields, $fcn );
			}
		}

		if( $item instanceof \Aimeos\MShop\Product\Item\Iface )
		{
			foreach( $item->getCatalogItems() as $catItem ) {
				$this->map( $catItem, $fields, $fcn );
			}

			foreach( $item->getSupplierItems() as $supItem ) {
				$this->map( $supItem, $fields, $fcn );
			}

			foreach( $item->getStockItems() as $stockItem ) {
				$this->map( $stockItem, $fields, $fcn );
			}
		}
	}


	/**
	 * Populates the map class property with the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 * @param array $fcn Associative list of resource types as keys and anonymous functions for generating the array entries as values
	 */
	protected function map( \Aimeos\MShop\Common\Item\Iface $item, array $fields, array $fcn = [] )
	{
		$id = $item->getId();
		$type = $item->getResourceType();

		if( isset( $this->map[$type][$id] ) || !$item->isAvailable() ) {
			return;
		}

		$attributes = $item->toArray();

		if( isset( $fields[$type] ) ) {
			$attributes = array_intersect_key( $attributes, $fields[$type] );
		}

		$entry = ['id' => $id, 'type' => $type, 'attributes' => $attributes];

		if( isset( $fcn[$type] ) && $fcn[$type] instanceof \Closure ) {
			$entry = $fcn[$type]( $item, $entry );
		}

		$this->map[$type][$id] = $entry; // first content, avoid infinite loops

		if( $item instanceof \Aimeos\MShop\Common\Item\Tree\Iface )
		{
			foreach( $item->getChildren() as $childItem )
			{
				if( $childItem->isAvailable() )
				{
					$rtype = $childItem->getResourceType();
					$rtype = ( $pos = strrpos( $rtype, '/' ) ) !== false ? substr( $rtype, $pos + 1 ) : $rtype;
					$entry['relationships'][$rtype]['data'][] = ['id' => $childItem->getId(), 'type' => $rtype];
					$this->map( $childItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\ListsRef\Iface )
		{
			foreach( $item->getListItems() as $listItem )
			{
				if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
				{
					$ltype = $listItem->getResourceType();
					$rtype = $refItem->getResourceType();
					$attributes = $listItem->toArray();

					if( isset( $fields[$ltype] ) ) {
						$attributes = array_intersect_key( $attributes, $fields[$ltype] );
					}

					$data = ['id' => $refItem->getId(), 'type' => $rtype, 'attributes' => $attributes];
					$entry['relationships'][$rtype]['data'][] = $data;
					$this->map( $refItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
		{
			foreach( $item->getPropertyItems() as $propItem )
			{
				if( $propItem->isAvailable() )
				{
					$propId = $propItem->getId();
					$rtype = $propItem->getResourceType();
					$entry['relationships'][$rtype]['data'][] = ['id' => $propId, 'type' => $rtype];
					$this->map( $propItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Product\Item\Iface )
		{
			foreach( $item->getCatalogItems() as $catItem )
			{
				if( $catItem->isAvailable() )
				{
					$catId = $catItem->getId();
					$rtype = $catItem->getResourceType();
					$entry['relationships'][$rtype]['data'][] = ['id' => $catId, 'type' => $rtype];
					$this->map( $catItem, $fields, $fcn );
				}
			}

			foreach( $item->getSupplierItems() as $supItem )
			{
				if( $supItem->isAvailable() )
				{
					$supId = $supItem->getId();
					$rtype = $supItem->getResourceType();
					$entry['relationships'][$rtype]['data'][] = ['id' => $supId, 'type' => $rtype];
					$this->map( $supItem, $fields, $fcn );
				}
			}

			foreach( $item->getStockItems() as $stockItem )
			{
				if( $stockItem->isAvailable() )
				{
					$stockId = $stockItem->getId();
					$rtype = $stockItem->getResourceType();
					$entry['relationships'][$rtype]['data'][] = ['id' => $stockId, 'type' => $rtype];
					$this->map( $stockItem, $fields, $fcn );
				}
			}
		}

		$this->map[$type][$id] = $entry; // full content
	}
}
