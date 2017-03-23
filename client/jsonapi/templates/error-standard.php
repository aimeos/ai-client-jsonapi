{
<?php if( isset( $this->errors ) ) : ?>
	"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>
<?php endif; ?>

}
