<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Client
 * @subpackage JsonApi
 */


$entries = [];
$data = $this->get( 'data', [] );
$type = $this->param( 'aggregate' );

foreach( $data as $key => $value ) {
	$entries[] = array( 'id' => $key, 'type' => $type, 'attributes' => $value );
}


?>
{
	"meta": {
		"total": <?= count( $data ); ?>,
		"prefix": <?= json_encode( $this->get( 'prefix' ) ); ?>,
		"content-baseurl": "<?= $this->config( 'resource/fs/baseurl' ); ?>"

		<?php if( $this->csrf()->name() != '' ) : ?>
			, "csrf": {
				"name": "<?= $this->csrf()->name(); ?>",
				"value": "<?= $this->csrf()->value(); ?>"
			}
		<?php endif; ?>

	},

	<?php if( isset( $this->errors ) ) : ?>
		"errors": <?= json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php else : ?>
		"data": <?= json_encode( $entries ); ?>

	<?php endif; ?>
}
