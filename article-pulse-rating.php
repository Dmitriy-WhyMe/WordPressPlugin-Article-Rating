<?php
/**
 * Plugin Name: Article Pulse Rating
 * Description: Плагин рейтинга статей через шорткод [article_rating].
 * Version: 1.1.0
 * Author: WhyMe
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: article-pulse-rating
 * Update URI: false
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'APR_PLUGIN_FILE' ) ) {
	define( 'APR_PLUGIN_FILE', __FILE__ );
}

if ( ! function_exists( 'apr_get_question_text' ) ) {
	function apr_get_question_text() {
		$default = 'Насколько полезным был этот материал?';
		$value   = get_option( 'apr_question_text', $default );
		$value   = is_string( $value ) ? trim( $value ) : '';
		return '' !== $value ? $value : $default;
	}
}

if ( ! function_exists( 'apr_votes_word' ) ) {
	function apr_votes_word( $count ) {
		$count  = absint( $count );
		$mod10  = $count % 10;
		$mod100 = $count % 100;

		if ( 1 === $mod10 && 11 !== $mod100 ) {
			return 'голос';
		}

		if ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
			return 'голоса';
		}

		return 'голосов';
	}
}

if ( ! function_exists( 'apr_print_assets_once' ) ) {
	function apr_print_assets_once() {
		static $printed = false;

		if ( $printed ) {
			return;
		}
		$printed = true;
		?>
		<style>
			.article-pulse-rating {
				margin-top: 32px;
				padding: 24px;
				border: 1px solid #d9e2ec;
				border-radius: 18px;
				background: linear-gradient(135deg, #f8fbff 0%, #eef6f1 100%);
			}
			.article-pulse-rating__inner {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 24px;
				flex-wrap: wrap;
			}
			.article-pulse-rating__eyebrow {
				margin: 0 0 8px;
				font-size: 12px;
				font-weight: 700;
				letter-spacing: .08em;
				text-transform: uppercase;
				color: #0d9866;
			}
			.article-pulse-rating__title {
				margin: 0 0 8px;
				font-size: 26px;
				line-height: 1.2;
				color: #102a43;
			}
			.article-pulse-rating__summary {
				margin: 0;
				font-size: 16px;
				color: #486581;
			}
			.article-pulse-rating__average {
				font-size: 24px;
				font-weight: 700;
				color: #102a43;
			}
			.article-pulse-rating__actions {
				display: flex;
				flex-direction: column;
				align-items: flex-end;
				gap: 10px;
			}
			.article-pulse-rating__stars {
				display: flex;
				gap: 8px;
			}
			.article-pulse-rating__star {
				padding: 0;
				border: 0;
				background: transparent;
				font-size: 34px;
				line-height: 1;
				color: #cbd2d9;
				cursor: pointer;
				transition: transform .2s ease, color .2s ease;
			}
			.article-pulse-rating__star:hover,
			.article-pulse-rating__star:focus-visible,
			.article-pulse-rating__star.is-active,
			.article-pulse-rating__star.is-selected {
				color: #f5a623;
			}
			.article-pulse-rating__star:hover,
			.article-pulse-rating__star:focus-visible {
				transform: translateY(-2px);
				outline: none;
			}
			.article-pulse-rating__star[disabled] {
				cursor: default;
			}
			.article-pulse-rating__message {
				margin: 0;
				font-size: 14px;
				color: #486581;
				text-align: right;
			}
			.article-pulse-rating.is-submitted .article-pulse-rating__message {
				color: #0d9866;
			}
			@media (max-width: 767px) {
				.article-pulse-rating {
					padding: 20px 16px;
				}
				.article-pulse-rating__title {
					font-size: 22px;
				}
				.article-pulse-rating__actions {
					align-items: flex-start;
					width: 100%;
				}
				.article-pulse-rating__message {
					text-align: left;
				}
			}
		</style>
		<script>
			(function () {
				function votesWord(count) {
					const mod10 = count % 10;
					const mod100 = count % 100;
					if (mod10 === 1 && mod100 !== 11) return 'голос';
					if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'голоса';
					return 'голосов';
				}

				function initRatingBlock(ratingBlock) {
					const stars = Array.from(ratingBlock.querySelectorAll('.article-pulse-rating__star'));
					const starsWrap = ratingBlock.querySelector('.article-pulse-rating__stars');
					const message = ratingBlock.querySelector('.article-pulse-rating__message');
					const average = ratingBlock.querySelector('.article-pulse-rating__average');
					const votes = ratingBlock.querySelector('.article-pulse-rating__votes');
					const postId = ratingBlock.dataset.postId;
					const nonce = ratingBlock.dataset.nonce;
					const ajaxUrl = ratingBlock.dataset.ajaxUrl;

					function selectedValue() {
						return Number(ratingBlock.dataset.userRating || 0);
					}

					function paintStars(value) {
						const selected = selectedValue();
						stars.forEach(function (star) {
							const starValue = Number(star.dataset.rating);
							star.classList.toggle('is-active', value >= starValue);
							star.classList.toggle('is-selected', selected >= starValue);
						});
					}

					if (selectedValue()) {
						ratingBlock.classList.add('is-submitted');
						paintStars(selectedValue());
					}

					stars.forEach(function (star) {
						star.addEventListener('mouseenter', function () {
							if (!ratingBlock.classList.contains('is-submitted')) {
								paintStars(Number(star.dataset.rating));
							}
						});

						star.addEventListener('focus', function () {
							if (!ratingBlock.classList.contains('is-submitted')) {
								paintStars(Number(star.dataset.rating));
							}
						});

						star.addEventListener('click', function () {
							if (ratingBlock.classList.contains('is-submitted')) {
								return;
							}

							const selectedRating = Number(star.dataset.rating);
							const payload = new URLSearchParams({
								action: 'apr_submit_rating',
								post_id: postId,
								rating: selectedRating,
								nonce: nonce
							});

							message.textContent = 'Сохраняем вашу оценку...';

							fetch(ajaxUrl, {
								method: 'POST',
								headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
								body: payload.toString()
							})
							.then(function (response) { return response.json(); })
							.then(function (response) {
								if (!response.success) {
									throw new Error(response.data && response.data.message ? response.data.message : 'Не удалось сохранить оценку.');
								}

								ratingBlock.classList.add('is-submitted');
								ratingBlock.dataset.userRating = String(selectedRating);
								paintStars(selectedRating);
								stars.forEach(function (button) { button.disabled = true; });

								average.textContent = Number(response.data.average).toLocaleString('ru-RU', {
									minimumFractionDigits: 1,
									maximumFractionDigits: 1
								});
								votes.textContent = '(' + response.data.count + ' ' + votesWord(response.data.count) + ')';
								message.textContent = response.data.message;
							})
							.catch(function (error) {
								message.textContent = error.message;
								paintStars(0);
							});
						});
					});

					if (starsWrap) {
						starsWrap.addEventListener('mouseleave', function () {
							paintStars(ratingBlock.classList.contains('is-submitted') ? selectedValue() : 0);
						});
					}
				}

				document.addEventListener('DOMContentLoaded', function () {
					const blocks = document.querySelectorAll('.article-pulse-rating');
					blocks.forEach(initRatingBlock);
				});
			})();
		</script>
		<?php
	}
}

if ( ! function_exists( 'apr_render_rating_block' ) ) {
	function apr_render_rating_block( $post_id ) {
		$post_id            = absint( $post_id );
		$rating_average     = (float) get_post_meta( $post_id, '_kb_rating_average', true );
		$rating_count       = (int) get_post_meta( $post_id, '_kb_rating_count', true );
		$user_rating_cookie = isset( $_COOKIE[ 'kb_rating_' . $post_id ] ) ? absint( $_COOKIE[ 'kb_rating_' . $post_id ] ) : 0;
		$question_text      = apr_get_question_text();

		ob_start();
		?>
		<div
			class="article-pulse-rating"
			data-post-id="<?php echo esc_attr( $post_id ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'article_pulse_rating' ) ); ?>"
			data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-user-rating="<?php echo esc_attr( $user_rating_cookie ); ?>"
		>
			<div class="article-pulse-rating__inner">
				<div class="article-pulse-rating__content">
					<p class="article-pulse-rating__eyebrow">Оцените статью</p>
					<h3 class="article-pulse-rating__title"><?php echo esc_html( $question_text ); ?></h3>
					<p class="article-pulse-rating__summary">
						<span class="article-pulse-rating__average"><?php echo $rating_count ? esc_html( number_format_i18n( $rating_average, 1 ) ) : '0.0'; ?></span>
						<span>/ 5</span>
						<span class="article-pulse-rating__votes">(<?php echo esc_html( $rating_count ); ?> <?php echo esc_html( apr_votes_word( $rating_count ) ); ?>)</span>
					</p>
				</div>
				<div class="article-pulse-rating__actions">
					<div class="article-pulse-rating__stars" role="radiogroup" aria-label="Оценка статьи">
						<?php for ( $star = 1; $star <= 5; $star++ ) : ?>
							<button
								type="button"
								class="article-pulse-rating__star<?php echo $user_rating_cookie >= $star ? ' is-selected' : ''; ?>"
								data-rating="<?php echo esc_attr( $star ); ?>"
								role="radio"
								aria-checked="<?php echo $user_rating_cookie === $star ? 'true' : 'false'; ?>"
								aria-label="<?php echo esc_attr( sprintf( 'Поставить %d из 5', $star ) ); ?>"
								<?php disabled( $user_rating_cookie > 0 ); ?>
							>
								<span aria-hidden="true">★</span>
							</button>
						<?php endfor; ?>
					</div>
					<p class="article-pulse-rating__message" aria-live="polite">
						<?php echo $user_rating_cookie ? 'Спасибо, ваша оценка уже сохранена.' : 'Нажмите на звезду, чтобы оставить оценку.'; ?>
					</p>
				</div>
			</div>
		</div>
		<?php
		apr_print_assets_once();
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'apr_rating_shortcode' ) ) {
	function apr_rating_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => 0,
			),
			$atts,
			'article_rating'
		);

		$post_id = absint( $atts['post_id'] );
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return '';
		}

		return apr_render_rating_block( $post_id );
	}
}
add_shortcode( 'article_rating', 'apr_rating_shortcode' );

if ( ! function_exists( 'apr_submit_rating' ) ) {
	function apr_submit_rating() {
		check_ajax_referer( 'article_pulse_rating', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$rating  = isset( $_POST['rating'] ) ? absint( wp_unslash( $_POST['rating'] ) ) : 0;

		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Некорректная статья.' ), 400 );
		}
		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array( 'message' => 'Оценка должна быть от 1 до 5.' ), 400 );
		}

		$cookie_name = 'kb_rating_' . $post_id;
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$current_average = (float) get_post_meta( $post_id, '_kb_rating_average', true );
			$current_count   = (int) get_post_meta( $post_id, '_kb_rating_count', true );
			wp_send_json_error(
				array(
					'message' => 'Вы уже оценивали эту статью.',
					'average' => $current_average,
					'count'   => $current_count,
				),
				409
			);
		}

		$rating_total = (int) get_post_meta( $post_id, '_kb_rating_total', true ) + $rating;
		$rating_count = (int) get_post_meta( $post_id, '_kb_rating_count', true ) + 1;
		$average      = round( $rating_total / $rating_count, 1 );

		update_post_meta( $post_id, '_kb_rating_total', $rating_total );
		update_post_meta( $post_id, '_kb_rating_count', $rating_count );
		update_post_meta( $post_id, '_kb_rating_average', $average );

		$secure = is_ssl();
		setcookie( $cookie_name, (string) $rating, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );

		wp_send_json_success(
			array(
				'message' => 'Спасибо за вашу оценку!',
				'average' => $average,
				'count'   => $rating_count,
				'rating'  => $rating,
			)
		);
	}
}
add_action( 'wp_ajax_apr_submit_rating', 'apr_submit_rating' );
add_action( 'wp_ajax_nopriv_apr_submit_rating', 'apr_submit_rating' );

if ( ! function_exists( 'apr_register_settings' ) ) {
	function apr_register_settings() {
		register_setting(
			'apr_settings_group',
			'apr_question_text',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Насколько полезным был этот материал?',
			)
		);
	}
}
add_action( 'admin_init', 'apr_register_settings' );

if ( ! function_exists( 'apr_add_options_page' ) ) {
	function apr_add_options_page() {
		add_options_page(
			'Article Pulse Rating',
			'Article Pulse Rating',
			'manage_options',
			'article-pulse-rating',
			'apr_render_options_page'
		);
	}
}
add_action( 'admin_menu', 'apr_add_options_page' );

if ( ! function_exists( 'apr_render_options_page' ) ) {
	function apr_render_options_page() {
		?>
		<div class="wrap">
			<h1>Article Pulse Rating</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'apr_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="apr_question_text">Текст вопроса</label></th>
						<td>
							<input id="apr_question_text" name="apr_question_text" type="text" value="<?php echo esc_attr( apr_get_question_text() ); ?>" class="regular-text" />
							<p class="description">Этот текст показывается в заголовке блока рейтинга.</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p><strong>Шорткод:</strong> <code>[article_rating]</code> или <code>[article_rating post_id="123"]</code></p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'apr_plugin_action_links' ) ) {
	function apr_plugin_action_links( $links ) {
		$settings_url = admin_url( 'options-general.php?page=article-pulse-rating' );
		$settings     = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}
}
add_filter( 'plugin_action_links_' . plugin_basename( APR_PLUGIN_FILE ), 'apr_plugin_action_links' );
