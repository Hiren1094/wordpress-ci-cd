<?php
/**
Template Name: Testing Template
 *
 * @package SKT Insurance
 */
get_header(); ?>
<div class="container">
<form>
	<input type="text" />
</form>
<?php
$test_var = isset( $_POST['test'] ) ? $_POST['test'] : '';
?>
</div><!-- .container --> 
<?php get_footer(); ?>
