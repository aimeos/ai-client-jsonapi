<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


?>
{
<?php if( isset( $this->errors ) ) : ?>
	"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>
<?php endif; ?>

}
