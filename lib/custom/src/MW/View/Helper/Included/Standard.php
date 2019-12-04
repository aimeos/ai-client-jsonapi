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
class Standard extends \Aimeos\MW\View\Helper\Base implements Iface
{
	private $map;


	/**
	 * Returns the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface|\Aimeos\MShop\Common\Item\Iface[] $item Object or objects to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 * @return array List of entries to include in the JSON:API response
	 */
	public function transform( $item, array $fields, array $fcn = [] )
	{
		$this->map = [];

		if( is_array( $item ) )
		{
			foreach( $item as $entry ) {
				$this->entry( $entry, $fields, $fcn );
			}
		}
		else
		{
			$this->entry( $item, $fields, $fcn );
		}

		$result = [];

		foreach( $this->map as $list )
		{
			foreach( $list as $entry ) {
				$result[] = $entry;
			}
		}

		return $result;
	}


	/**
	 * Processes a single item to create the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
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
			foreach( $item->getAddressItems() as $addItem ) {
				$this->map( $addItem, $fields );
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
		{
			foreach( $item->getListItems() as $listItem )
			{
				if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() ) {
					$this->map( $refItem, $fields );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
		{
			foreach( $item->getPropertyItems() as $propertyItem ) {
				$this->map( $propertyItem, $fields );
			}
		}
	}


	/**
	 * Populates the map class property with the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 */
	protected function map( \Aimeos\MShop\Common\Item\Iface $item, array $fields )
	{
		$id = $item->getId();
		$type = $item->getResourceType();

		if( isset( $this->map[$type][$id] ) ) {
			return;
		}

		$attributes = $item->toArray();

		if( isset( $fields[$type] ) ) {
			$attributes = array_intersect_key( $attributes, $fields[$type] );
		}

		$entry = ['id' => $id, 'type' => $type, 'attributes' => $attributes];
		$this->map[$type][$id] = $entry; // first content, avoid infinite loops

		if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
		{
			foreach( $item->getListItems() as $listItem )
			{
				if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
				{
					$reftype = $refItem->getResourceType();
					$data = ['id' => $refItem->getId(), 'type' => $reftype, 'attributes' => $listItem->toArray()];
					$entry['relationships'][$reftype]['data'][] = $data;
					$this->map( $refItem, $fields );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
		{
			foreach( $item->getPropertyItems() as $propItem )
			{
				$propId = $propItem->getId();
				$propType = $propItem->getResourceType();
				$entry['relationships'][$propType]['data'][] = ['id' => $propId, 'type' => $propType];
				$this->map( $propItem, $fields );
			}
		}

		$this->map[$type][$id] = $entry; // full content
	}
}
