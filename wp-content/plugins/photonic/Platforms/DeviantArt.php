<?php
namespace Photonic_Plugin\Platforms;

use Photonic_Plugin\Components\Album_List;
use Photonic_Plugin\Components\Error;
use Photonic_Plugin\Components\Pagination;
use Photonic_Plugin\Components\Photo_List;
use Photonic_Plugin\Core\Photonic;
use Photonic_Plugin\Components\Album;
use Photonic_Plugin\Components\Photo;

require_once 'OAuth2.php';
require_once 'Level_One_Module.php';
require_once 'Level_Two_Module.php';
require_once 'Pageable.php';

/**
 * Fetches photos from a user's Google Photos account.
 * Lacks support for dual title / description fields, doesn't provide download URLs, and video support is ambiguous.
 */
class DeviantArt extends OAuth2 implements Level_One_Module, Level_Two_Module, Pageable {
	public $refresh_token_valid;

	protected function __construct() {
		parent::__construct();
		global $photonic_deviantart_client_id, $photonic_deviantart_client_secret, $photonic_deviantart_refresh_token;

		if (!empty($photonic_deviantart_client_id) && !empty($photonic_deviantart_client_secret)) {
			$this->client_id     = trim($photonic_deviantart_client_id);
			$this->client_secret = trim($photonic_deviantart_client_secret);

			$transient_refresh = get_transient('photonic_deviantart_refresh_token_' . $this->client_id);
			if (!empty($transient_refresh)) {
				$photonic_deviantart_refresh_token = $transient_refresh;
			}
		}

		$this->provider            = 'deviantart';
		$this->oauth_version       = '2.0';
		$this->response_type       = 'code';
		$this->scope               = 'basic';
		$this->link_lightbox_title = false; // empty($photonic_google_disable_title_link);
		$this->oauth_done          = false;

		// Documentation
		$this->doc_links = [
			'general' => 'https://aquoid.com/plugins/photonic/deviantart/',
			'photos'  => 'https://aquoid.com/plugins/photonic/deviantart/',
			'albums'  => 'https://aquoid.com/plugins/photonic/deviantart/',
		];

		$this->authenticate($photonic_deviantart_refresh_token);
	}

	public function get_gallery_images($attr = []): array {
		global $photonic_deviantart_refresh_token;

		$this->push_to_stack('Get Gallery Images');
		$attr = array_merge(
			$this->common_parameters,
			[
				'caption'         => 'title',
				'thumb_size'      => '150',
				'main_size'       => '1600',
				'tile_size'       => '1600',
				'crop_thumb'      => 'crop',

				// Google ...
				'count'           => 100,
				'media'           => 'photos',
				'video_size'      => 'dv',
				'date_filters'    => '',
				'content_filters' => '',
				'access'          => 'all',
			],
			$attr
		);

		$attr = array_map('trim', $attr);

		$attr['overlay_size']       = empty($attr['overlay_size']) ? $attr['thumb_size'] : $attr['overlay_size'];
		$attr['overlay_video_size'] = empty($attr['overlay_video_size']) ? $attr['video_size'] : $attr['overlay_video_size'];
		$attr['overlay_crop']       = empty($attr['overlay_crop']) ? $attr['crop_thumb'] : $attr['overlay_crop'];

		if (empty($this->client_id)) {
			$this->pop_from_stack();
			return [new Error(esc_html__('DeviantArt Client ID not defined.', 'photonic') . Photonic::doc_link($this->doc_links['general']))];
		}

		if (empty($this->client_secret)) {
			$this->pop_from_stack();
			return [new Error(esc_html__('DeviantArt Client Secret not defined.', 'photonic') . Photonic::doc_link($this->doc_links['general']))];
		}

		if (empty($photonic_deviantart_refresh_token)) {
			$this->pop_from_stack();
			return [new Error(sprintf(esc_html__('DeviantArt Refresh Token not defined. Please authenticate from %s.', 'photonic'), '<em>Photonic &rarr; Authentication</em>') . Photonic::doc_link($this->doc_links['general']))];
		}

		if (!$this->refresh_token_valid) {
			$this->pop_from_stack();
			$error = sprintf(esc_html__('DeviantArt Refresh Token invalid. Please authenticate from %s.', 'photonic'), '<em>Photonic &rarr; Authentication</em>');
			if (!empty($this->auth_error)) {
				$error .= '<br/>' . sprintf(esc_html__('Error encountered during authentication: %s', 'photonic'), '<br/><pre>' . $this->auth_error . '</pre>');
			}
			return [new Error($error . Photonic::doc_link($this->doc_links['general']))];
		}

		if (empty($attr['view'])) {
			$this->pop_from_stack();
			return [new Error(sprintf(esc_html__('The %s parameter is mandatory for the shortcode.', 'photonic'), '<code>view</code>'))];
		}

		$query_urls = [];
		if ('galleries' === $attr['view']) {
			$query_urls[] = 'https://www.deviantart.com/api/v1/oauth2/gallery/9E8178D7-1A81-78A8-84EE-60B224339310';
			$query_urls[] = 'https://www.deviantart.com/api/v1/oauth2/deviation/A1405364-F308-5B73-131F-C51EBAFBC936';
		}

		$out = $this->make_call($query_urls, $attr);
		$this->pop_from_stack();
		if (!empty($this->stack_trace[$this->gallery_index])) {
			$out[] = $this->stack_trace[$this->gallery_index];
		}

		return [];
	}

	private function make_call($query_urls, $attr): array {
		global $photonic_deviantart_refresh_token;
		$this->push_to_stack('Making calls');

		$components = [];

		foreach ($query_urls as $query_url) {
			$this->push_to_stack("Query $query_url");
			if (!empty($photonic_deviantart_refresh_token) && !empty($this->access_token)) {
				$query_url = add_query_arg('access_token', $this->access_token, $query_url);
			}

			$response = wp_remote_request($query_url);
			if (!is_wp_error($response)) {
				$this->push_to_stack('Processing Response');
				$body = wp_remote_retrieve_body($response);

				$output = $this->process_response($body, $attr);

				if (!is_null($output)) {
					$components[] = $output;
				}

				$this->pop_from_stack();
			}
			else {
				$this->pop_from_stack(); // "Query $query_url"
				$this->pop_from_stack(); // 'Making calls'
				return [new Error($response->get_error_message())];
			}

			$this->pop_from_stack();
		}

		$this->pop_from_stack();
		return $components;
	}

	private function process_response($body, $short_code) {
		global $photonic_google_photo_title_display, $photonic_google_photos_per_row_constraint, $photonic_gallery_template_page,
		       $photonic_google_photos_constrain_by_count, $photonic_google_photo_pop_title_display, $photonic_google_hide_album_photo_count_display;

		if (!empty($body)) {
			$body            = json_decode($body);
			$row_constraints = ['constraint-type' => $photonic_google_photos_per_row_constraint, 'count' => $photonic_google_photos_constrain_by_count];
			$display         = $short_code['display'];

			if (isset($body->results)) {
				print_r($body->results);
			}
/*			if (isset($body->albums) || isset($body->sharedAlbums)) {
				$albums = $body->albums ?? $body->sharedAlbums;

				$pagination = $this->get_pagination($body, $short_code);
				$dummy_options = [];
				$albums     = $this->build_level_2_objects($albums, $short_code, $remove, $dummy_options, $pagination);

				global $photonic_google_photos_layout_engine;
				$layout_engine = $short_code['layout_engine'] ?? $photonic_google_photos_layout_engine;
				if ('css' === $layout_engine) {
					$this->update_thumbnail_information($albums, $short_code);
				}

				if ($deferred) {
					return $albums;
				}

				$album_list = new Album_List($short_code);

				$album_list->albums                = $albums;
				$album_list->row_constraints       = $row_constraints;
				$album_list->type                  = 'albums';
				$album_list->singular_type         = 'album';
				$album_list->title_position        = $photonic_google_photo_title_display;
				$album_list->level_1_count_display = !empty($photonic_google_hide_album_photo_count_display);
				$album_list->pagination            = $pagination;
				$album_list->album_opens_gallery   = ('page' === $short_code['popup'] && !empty($photonic_gallery_template_page) && is_string(get_post_status($photonic_gallery_template_page)));

				return $album_list;
			}
			elseif (isset($body->mediaItems)) {
				if ('local' === $display) {
					$title_position = $photonic_google_photo_title_display;
				}
				else {
					$row_constraints = ['constraint-type' => 'padding'];
					$title_position  = $photonic_google_photo_pop_title_display;
				}

				$pagination = $this->get_pagination($body, $short_code);

				$photos = $body->mediaItems;
				$photos = $this->build_level_1_objects($photos, $short_code);

				$photo_list                  = new Photo_List($short_code);
				$photo_list->photos          = $photos;
				$photo_list->title_position  = $title_position;
				$photo_list->row_constraints = $row_constraints;
				$photo_list->parent          = 'album';
				$photo_list->pagination      = $pagination;

				return $photo_list;
			}
			elseif (isset($body->error)) {
				$err = esc_html__('Failed to get data. Error:', 'photonic') . "<br/><code>\n";
				$err .= $body->error->message;
				$err .= "</code><br/>\n";

				return new Error($err);
			}*/
		}
		else {
			$err = esc_html__('Failed to get data. Error:', 'photonic') . "<br/><code>\n";
			$err .= $body;
			$err .= "</code><br/>\n";

			return new Error($err);
		}

		return null;
	}

	public function build_level_1_objects($response, array $short_code, $module_parameters = [], $options = []): array {
		// TODO: Implement build_level_1_objects() method.
	}

	public function build_level_2_objects($objects_or_response, array $short_code, array $filter_list = [], array &$options = [], Pagination &$pagination = null): array {
		// TODO: Implement build_level_2_objects() method.
	}

	public function authentication_URL() {
		return 'https://www.deviantart.com/oauth2/authorize';
	}

	public function access_token_URL() {
		return 'https://www.deviantart.com/oauth2/token';
	}

	public function renew_token($refresh_token): array {
		$token    = [];
		$error    = '';
//print_r($refresh_token."<br/>");
		$response = Photonic::http(
			$this->access_token_URL(),
			'GET',
			[
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'refresh_token' => $refresh_token,
				'grant_type'    => 'refresh_token'
			]
		);
//print_r($response);

		if (!is_wp_error($response)) {
			$token = $this->parse_token($response);
			if (!empty($token)) {
				$token['client_id'] = $this->client_id;
			}
			set_transient('photonic_' . $this->provider . '_token', $token, $token['oauth_token_expires']);
			set_transient('photonic_deviantart_refresh_token_' . $this->client_id, $token['oauth_refresh_token'], 90 * 24 * 3600);
			if (empty($token)) {
				$error = print_r(wp_remote_retrieve_body($response), true);
			}
		}
		else {
			$error = $response->get_error_message();
		}

		return [$token, $error];
	}

	protected function set_token_validity($validity) {
		$this->refresh_token_valid = $validity;
	}

	public function is_token_expiring_soon($soon_limit) {
		// TODO: Implement is_token_expiring_soon() method.
	}

	public function get_pagination($entity, array $short_code = []): Pagination {
		// TODO: Implement get_pagination() method.
	}
}
