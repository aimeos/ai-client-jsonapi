<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


namespace Aimeos\Client\JsonApi;


/**
 * JSON API common client
 *
 * @package Client
 * @subpackage JsonApi
 */
class Base
{
	private $view;
	private $context;
	private $templatePaths;
	private $path;


	/**
	 * Initializes the client
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context MShop context object
	 * @param \Aimeos\MW\View\Iface $view View object
	 * @param array $templatePaths List of file system paths where the templates are stored
	 * @param string $path Name of the client separated by slashes, e.g "product/property"
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MW\View\Iface $view, array $templatePaths, $path )
	{
		$this->view = $view;
		$this->context = $context;
		$this->templatePaths = $templatePaths;
		$this->path = $path;
	}


	/**
	 * Returns the context item object
	 *
	 * @return \Aimeos\MShop\Context\Item\Iface Context object
	 */
	protected function getContext()
	{
		return $this->context;
	}


	/**
	 * Returns the path to the client
	 *
	 * @return string Client path, e.g. "product/property"
	 */
	protected function getPath()
	{
		return $this->path;
	}


	/**
	 * Returns the paths to the template files
	 *
	 * @return array List of file system paths
	 */
	protected function getTemplatePaths()
	{
		return $this->templatePaths;
	}


	/**
	 * Returns the view object
	 *
	 * @return \Aimeos\MW\View\Iface View object
	 */
	protected function getView()
	{
		return $this->view;
	}


	/**
	 * Initializes the criteria object based on the given parameter
	 *
	 * @param \Aimeos\MW\Criteria\Iface $criteria Criteria object
	 * @param array $params List of criteria data with condition, sorting and paging
	 * @return \Aimeos\MW\Criteria\Iface Initialized criteria object
	 */
	protected function initCriteria( \Aimeos\MW\Criteria\Iface $criteria, array $params )
	{
		$this->initCriteriaConditions( $criteria, $params );
		$this->initCriteriaSortations( $criteria, $params );
		$this->initCriteriaSlice( $criteria, $params );

		return $criteria;
	}


	/**
	 * Initializes the criteria object with conditions based on the given parameter
	 *
	 * @param \Aimeos\MW\Criteria\Iface $criteria Criteria object
	 * @param array $params List of criteria data with condition, sorting and paging
	 */
	protected function initCriteriaConditions( \Aimeos\MW\Criteria\Iface $criteria, array $params )
	{
		if( !isset( $params['filter'] ) ) {
			return;
		}

		$existing = $criteria->getConditions();
		$criteria->setConditions( $criteria->toConditions( (array) $params['filter'] ) );

		$expr = array( $criteria->getConditions(), $existing );
		$criteria->setConditions( $criteria->combine( '&&', $expr ) );
	}


	/**
	 * Initializes the criteria object with the slice based on the given parameter.
	 *
	 * @param \Aimeos\MW\Criteria\Iface $criteria Criteria object
	 * @param array $params List of criteria data with condition, sorting and paging
	 */
	protected function initCriteriaSlice( \Aimeos\MW\Criteria\Iface $criteria, array $params )
	{
		$start = ( isset( $params['page']['offset'] ) ? (int) $params['page']['offset'] : 0 );
		$size = ( isset( $params['page']['limit'] ) ? (int) $params['page']['limit'] : 25 );

		$criteria->setSlice( $start, $size );
	}


	/**
	 * Initializes the criteria object with sortations based on the given parameter
	 *
	 * @param \Aimeos\MW\Criteria\Iface $criteria Criteria object
	 * @param array $params List of criteria data with condition, sorting and paging
	 */
	protected function initCriteriaSortations( \Aimeos\MW\Criteria\Iface $criteria, array $params )
	{
		if( !isset( $params['sort'] ) ) {
			return;
		}

		$sortation = array();

		foreach( explode( ',', $params['sort'] ) as $sort )
		{
			if( $sort[0] === '-' ) {
				$sortation[] = $criteria->sort( '-', substr( $sort, 1 ) );
			} else {
				$sortation[] = $criteria->sort( '+', $sort );
			}
		}

		$criteria->setSortations( $sortation );
	}
}
