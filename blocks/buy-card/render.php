<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rdb_render_buy_card( $attributes ) {
	$pick_tag   = isset( $attributes['pickTag'] ) ? trim( $attributes['pickTag'] ) : '';
	$image_url  = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';
	$image_alt  = isset( $attributes['imageAlt'] ) ? $attributes['imageAlt'] : '';
	$name       = isset( $attributes['name'] ) ? $attributes['name'] : '';
	$type       = isset( $attributes['productType'] ) ? $attributes['productType'] : '';
	$specs      = isset( $attributes['specs'] ) && is_array( $attributes['specs'] ) ? $attributes['specs'] : array();
	$fine_print = isset( $attributes['finePrint'] ) ? $attributes['finePrint'] : '';
	$cta_label  = isset( $attributes['ctaLabel'] ) ? $attributes['ctaLabel'] : 'View product';
	$cta_url    = isset( $attributes['ctaUrl'] ) ? $attributes['ctaUrl'] : '#';

	ob_start();
	?>
	<article class="rdb-buy-card">
		<div class="rdb-buy-media">
			<?php if ( $image_url ) : ?>
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" />
			<?php endif; ?>
		</div>
		<div class="rdb-buy-body">
			<?php if ( $pick_tag ) : ?>
				<span class="rdb-buy-pick-tag"><?php echo esc_html( $pick_tag ); ?></span>
			<?php endif; ?>
			<div class="rdb-buy-name"><?php echo esc_html( $name ); ?></div>
			<?php if ( $type ) : ?>
				<div class="rdb-buy-type"><?php echo esc_html( $type ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $specs ) ) : ?>
				<div class="rdb-buy-specs">
					<?php foreach ( $specs as $i => $spec ) : ?>
						<?php if ( $i > 0 ) : ?><span class="rdb-sep">/</span><?php endif; ?>
						<span><strong><?php echo esc_html( $spec['value'] ?? '' ); ?></strong> <?php echo esc_html( $spec['label'] ?? '' ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( $fine_print ) : ?>
				<p class="rdb-fine-print"><?php echo esc_html( $fine_print ); ?></p>
			<?php endif; ?>
			<a class="rdb-buy-cta" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}
