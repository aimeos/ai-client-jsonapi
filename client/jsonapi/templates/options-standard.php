<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'options' );
$config = $this->config( 'client/jsonapi/url/config', [] );

$resources = $default = [];

if( ( $currency = $this->param( 'currency' ) ) != null ) {
	$default['currency'] = $currency;
}

if( ( $locale = $this->param( 'locale' ) ) != null ) {
	$default['locale'] = $locale;
}

foreach( $this->get( 'resources', [] ) as $resource ) {
	$resources[$resource] = $this->url( $target, $cntl, $action, ['resource' => $resource] + $default, [], $config );
}


?>
{
	"meta": {
		"prefix": <?= json_encode( $this->get( 'prefix' ) ); ?>,
		"content-baseurl": "<?= $this->config( 'client/html/common/content/baseurl' ); ?>"

		<?php if( $this->csrf()->name() != '' ) : ?>
			, "csrf": {
				"name": "<?= $this->csrf()->name(); ?>",
				"value": "<?= $this->csrf()->value(); ?>"
			}
		<?php endif; ?>
		<?php if( !empty( $resources ) ) : ?>

			, "resources": <?= json_encode( $resources ); ?>

		<?php endif; ?>
		<?php if( isset( $this->filter ) ) : ?>

			, "filter": <?= json_encode( $this->filter, JSON_PRETTY_PRINT ); ?>

		<?php endif; ?>
		<?php if( isset( $this->sort ) ) : ?>

			, "sort": <?= json_encode( $this->sort, JSON_PRETTY_PRINT ); ?>

		<?php endif; ?>
		<?php if( isset( $this->attributes ) ) : ?>

			, "attributes": <?= json_encode( $this->attributes, JSON_PRETTY_PRINT ); ?>

		<?php endif; ?>
	}

	<?php if( isset( $this->errors ) ) : ?>

		, "errors": <?= json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>
}
