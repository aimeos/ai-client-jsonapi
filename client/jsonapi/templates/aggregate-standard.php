<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
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
		"total": <?php echo count( $data ); ?>

	},

	<?php if( isset( $this->errors ) ) : ?>

		"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php else : ?>

		"data": <?php echo json_encode( $entries ); ?>

	<?php endif; ?>
}
