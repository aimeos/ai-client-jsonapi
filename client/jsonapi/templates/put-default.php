{
<?php if( isset( $this->errors ) ) : ?>
	"errors": <?php echo $this->partial( $this->config( 'client/jsonapi/partials/template-errors', 'partials/errors-standard.php' ), array( 'errors' => $this->errors ) ); ?>
<?php endif; ?>

}
