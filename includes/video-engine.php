<?php
/**
 * HKDEV Video Embed Engine (HKDEV Shop Elements plugin).
 *
 * Renders a "lite" YouTube embed: a poster image with a play button that loads
 * the real YouTube iframe only when clicked. Falls back to the normal watch
 * page when JavaScript is unavailable. Used by the manufacturing-process block.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Video_Engine
 */
class Video_Engine {

	/**
	 * @var ?Video_Engine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Video_Engine
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Default render configuration.
	 *
	 * @return array
	 */
	public function defaults() {
		return [
			'anchor'           => '',
			'heading'          => '',
			'description'      => '',
			'video'            => '',
			'poster'           => '',
			'play_label'       => __( 'Play video', 'hkdev-shop-elements' ),
			'show_heading'     => true,
			'show_description' => true,
			'show_separator'   => true,
		];
	}

	/**
	 * Extract a YouTube video id from any common URL shape (or a bare id).
	 *
	 * @param string $url URL or id.
	 * @return string
	 */
	public function video_id( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		if ( preg_match( '~^[A-Za-z0-9_\-]{11}$~', $url ) ) {
			return $url;
		}

		if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_\-]{11})~i', $url, $match ) ) {
			return $match[1];
		}

		return '';
	}

	/**
	 * Render the video block.
	 *
	 * @param array $config Render configuration.
	 * @return string
	 */
	public function render( $config = [] ) {
		$config = wp_parse_args( $config, $this->defaults() );

		$video_id = $this->video_id( $config['video'] );
		if ( '' === $video_id ) {
			return '';
		}

		$watch_url = 'https://www.youtube.com/watch?v=' . $video_id;
		$embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&rel=0';

		$poster = ! empty( $config['poster'] ) ? $config['poster'] : 'https://i.ytimg.com/vi/' . $video_id . '/maxresdefault.jpg';
		$fallback = 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';
		$play_label = '' !== trim( (string) $config['play_label'] ) ? $config['play_label'] : __( 'Play video', 'hkdev-shop-elements' );
		$heading    = ! empty( $config['show_heading'] ) ? trim( (string) $config['heading'] ) : '';
		$description = ! empty( $config['show_description'] ) ? trim( (string) $config['description'] ) : '';

		ob_start();
		?>
		<section class="hkdev-yt"<?php echo $config['anchor'] ? ' id="' . esc_attr( $config['anchor'] ) . '"' : ''; ?>>
			<div class="hkdev-yt-container">
				<?php if ( '' !== $heading ) : ?>
					<h2 class="hkdev-yt-heading"><?php echo esc_html( $heading ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $config['show_separator'] ) ) : ?>
					<div class="hkdev-yt-separator" aria-hidden="true"></div>
				<?php endif; ?>

				<?php if ( '' !== $description ) : ?>
					<div class="hkdev-yt-description"><?php echo wp_kses_post( $description ); ?></div>
				<?php endif; ?>

				<div class="hkdev-yt-video-wrap">
					<a
						href="<?php echo esc_url( $watch_url ); ?>"
						class="hkdev-yt-lite"
						data-youtube-id="<?php echo esc_attr( $video_id ); ?>"
						data-youtube-embed="<?php echo esc_url( $embed_url ); ?>"
						data-youtube-fallback="<?php echo esc_url( $fallback ); ?>"
						aria-label="<?php echo esc_attr( $play_label ); ?>"
						target="_blank"
						rel="noopener"
					>
						<img
							src="<?php echo esc_url( $poster ); ?>"
							alt=""
							loading="lazy"
							decoding="async"
							aria-hidden="true"
						>
						<span class="hkdev-yt-play" aria-hidden="true">
							<svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
						</span>
					</a>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
