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

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VideoEngine
 */
class VideoEngine {

	/**
	 * @var ?VideoEngine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return VideoEngine
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
	 * Accepts watch / share / embed / Shorts / live links and a plain 11
	 * character id. The id may sit anywhere in the query string, so links like
	 * `watch?si=...&v=ID` are handled too — not just `watch?v=ID`.
	 *
	 * @param string $url URL or id.
	 * @return string Video id, or an empty string when none is found.
	 */
	public static function youtube_id( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		if ( preg_match( '~^[A-Za-z0-9_\-]{11}$~', $url ) ) {
			return $url;
		}

		if ( preg_match( '~youtu\.be/([A-Za-z0-9_\-]{11})~i', $url, $match ) ) {
			return $match[1];
		}

		if ( preg_match( '~youtube(?:-nocookie)?\.com/(?:embed|shorts|live|v)/([A-Za-z0-9_\-]{11})~i', $url, $match ) ) {
			return $match[1];
		}

		if ( preg_match( '~[?&]v=([A-Za-z0-9_\-]{11})~i', $url, $match ) ) {
			return $match[1];
		}

		return '';
	}

	/**
	 * Build a YouTube embed URL for the iframe player.
	 *
	 * The player must be loaded from www.youtube.com/embed/<id> with a full
	 * parameter set. Together with the `referrerpolicy` attribute set on the
	 * iframe (see assets/js/video.js and assets/js/reviews.js) this is what
	 * stops YouTube from answering with
	 * "Error 153: Video player configuration error" — that error is raised by
	 * YouTube when the embedding request carries no usable Referer.
	 *
	 * @param string $id       Video id.
	 * @param bool   $autoplay Whether to autoplay the video.
	 * @return string
	 */
	public static function youtube_embed_url( $id, $autoplay = true ) {
		$id = trim( (string) $id );

		if ( '' === $id ) {
			return '';
		}

		$args = [
			'rel'            => '0',
			'playsinline'    => '1',
			'modestbranding' => '1',
		];

		if ( $autoplay ) {
			$args['autoplay'] = '1';
		}

		return 'https://www.youtube.com/embed/' . rawurlencode( $id ) . '?' . http_build_query( $args );
	}

	/**
	 * Normal YouTube watch URL for a video id.
	 *
	 * @param string $id Video id.
	 * @return string
	 */
	public static function youtube_watch_url( $id ) {
		return 'https://www.youtube.com/watch?v=' . rawurlencode( trim( (string) $id ) );
	}

	/**
	 * Render the video block.
	 *
	 * @param array $config Render configuration.
	 * @return string
	 */
	public function render( $config = [] ) {
		$config = wp_parse_args( $config, $this->defaults() );

		$video_id = self::youtube_id( $config['video'] );
		if ( '' === $video_id ) {
			return '';
		}

		$watch_url = self::youtube_watch_url( $video_id );
		$embed_url = self::youtube_embed_url( $video_id );

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
