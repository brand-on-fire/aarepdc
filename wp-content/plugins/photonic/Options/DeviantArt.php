<?php

namespace Photonic_Plugin\Options;

use Photonic_Plugin\Core\Utilities;

class DeviantArt extends Option_Tab {
	protected function __construct() {
		$this->options = [
			[
				'name'     => "How To",
				'desc'     => "Control generic settings for the plugin",
				'category' => 'deviantart-how-to',
				'buttons'  => 'no-buttons',
				'type'     => 'section',
			],

			[
				'name'     => "Creating a gallery",
				'desc'     => "<strong>Please complete the setup steps in the \"DeviantArt Settings\" tab before attempting to create a gallery!</strong> <br/><br/>
			To create a gallery with DeviantArt content you can use either a <strong><em>Gutenberg Block</em></strong> 
			or the <em>Add / Edit Photonic Gallery</em> button in the <strong><em>Classic Editor</em></strong>:<br/><br/>
			<img src='" . PHOTONIC_URL . "Options/screenshots/Flickr-1.png' style='max-width: 600px;' alt='Wizard' />",
				'grouping' => 'deviantart-how-to',
				'type'     => 'blurb',
			],

			[
				'name'     => "DeviantArt settings",
				'desc'     => "Control settings for DeviantArt",
				'category' => 'deviantart-settings',
				'type'     => 'section',
			],

			[
				'name'     => "DeviantArt Client ID",
				'desc'     => "To make use of the DeviantArt functionality you have to use your DeviantArt Client ID.
					You can <a href='https://www.deviantart.com/developers/register'>request an id online</a> if you don't have one.
					Note that you are responsible for following DeviantArt API's <a href='https://www.deviantart.com/about/policy/api/'>License Agreement</a>.
					As a part of the Client ID setup process:
					<ol>
						<li>For the option 'OAuth2 Grant Type', select 'Authorization Code'.</li>
						<li>Make sure that you add these as your Redirect URIs:
							<ol>
								<li><code>" . esc_url(admin_url('admin.php?page=photonic-auth&source=deviantart')) . "</code></li>
							</ol>
							<strong>Without the above your authentication will not work.</strong>
						</li>
					</ol>",
				'id'       => 'deviantart_client_id',
				'grouping' => 'deviantart-settings',
				'type'     => 'text'
			],

			[
				'name'     => "DeviantArt Client Secret",
				'desc'     => "Please enter your DeviantArt API secret.",
				'id'       => 'deviantart_client_secret',
				'grouping' => 'deviantart-settings',
				'type'     => 'text'
			],

			[
				'name'     => "DeviantArt Refresh Token",
				'desc'     => "Please enter your DeviantArt API secret.",
				'id'       => 'deviantart_refresh_token',
				'grouping' => 'deviantart-settings',
				'type'     => 'text'
			],
		];
	}
}
