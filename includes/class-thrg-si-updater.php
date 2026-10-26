<?php
/**
 * Update checks against this plugin's GitHub releases.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p7\Vcs\Api;

/**
 * Points WordPress at GitHub releases for this plugin's updates.
 *
 * Uses the vendored Plugin Update Checker library (`plugin-update-checker/`).
 * A release's own uploaded zip asset is used instead of GitHub's
 * auto-generated source archive, because the auto-generated archive extracts
 * into a version-stamped folder name, which would change the installed
 * plugin's directory name and deactivate it on update.
 *
 * Release requirements: tag `vX.Y.Z` with an uploaded asset named
 * `thrg-site-inspector.X.Y.Z.zip` whose single top-level folder is
 * `thrg-site-inspector/`..
 *
 * A private repository is invisible to an anonymous update check, so this
 * does nothing until the repository is public.
 */
final class THRG_SI_Updater {

	const REPOSITORY_URL = 'https://github.com/hackrepair/thrg-site-inspector/';
	const BRANCH         = 'main';
	const ASSET_PATTERN  = '/^thrg-site-inspector\.[0-9]+(?:\.[0-9]+){2}\.zip$/';

	/**
	 * @var \YahnisElsts\PluginUpdateChecker\v5\Vcs\Api|null
	 */
	private $checker = null;

	/**
	 * Build and configure the update checker.
	 *
	 * No-ops rather than fataling if the vendored library is missing.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @return void
	 */
	public function init( $plugin_file ) {
		if ( ! class_exists( PucFactory::class ) ) {
			return;
		}

		$this->checker = PucFactory::buildUpdateChecker( self::REPOSITORY_URL, $plugin_file, basename( dirname( $plugin_file ) ) );
		$this->checker->setBranch( self::BRANCH );

		$api = $this->checker->getVcsApi();

		// A different loaded version of the update checker may lack one of the
		// controls below. Disable its hooks instead of offering a source archive.
		if ( ! $api || ! method_exists( $api, 'enableReleaseAssets' ) ||
			! method_exists( $api, 'setReleaseFilter' ) || ! method_exists( $this->checker, 'addFilter' ) ) {
			$this->checker->removeHooks();
			$this->checker = null;
			return;
		}

		require_once __DIR__ . '/class-thrg-si-update-notices.php';
		new THRG_SI_Update_Notices( $this->checker );

		$api->enableReleaseAssets( self::ASSET_PATTERN, Api::REQUIRE_RELEASE_ASSETS );

		// Only the latest-release strategy: the tag and branch fallbacks both
		// download an auto-generated source archive.
		$this->checker->addFilter(
			'vcs_update_detection_strategies',
			static function ( $strategies ) {
				return isset( $strategies[ Api::STRATEGY_LATEST_RELEASE ] )
					? array( Api::STRATEGY_LATEST_RELEASE => $strategies[ Api::STRATEGY_LATEST_RELEASE ] )
					: array();
			}
		);

		// The first asset matching ASSET_PATTERN is the one selected; require it
		// to match the release tag exactly.
		$api->setReleaseFilter(
			static function ( $version, $release ) {
				if ( ! is_string( $version ) || ! preg_match( '/^[0-9]+(?:\.[0-9]+){2}$/', $version ) ||
					! isset( $release->tag_name, $release->assets ) ||
					'v' . $version !== $release->tag_name || ! is_array( $release->assets ) ) {
					return false;
				}

				$expected = 'thrg-site-inspector.' . $version . '.zip';
				foreach ( $release->assets as $asset ) {
					if ( isset( $asset->name ) && is_string( $asset->name ) &&
						preg_match( self::ASSET_PATTERN, $asset->name ) ) {
						return $expected === $asset->name;
					}
				}

				return false;
			},
			Api::RELEASE_FILTER_SKIP_PRERELEASE,
			1
		);

		// The bundled Markdown renderer (Parsedown 1.6.0, unmodified upstream) predates its
		// XSS and class-injection fixes. Clean what it produces before WordPress shows it.
		$this->checker->addFilter( 'request_info_result', array( __CLASS__, 'clean_info' ), 20, 2 );
	}

	/**
	 * HTML tags and attributes allowed in update details: WordPress's post allowlist without class attributes.
	 *
	 * @return array
	 */
	public static function allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );
		foreach ( $allowed as $tag => $attributes ) {
			if ( is_array( $attributes ) ) {
				unset( $allowed[ $tag ]['class'] );
			}
		}
		return $allowed;
	}

	/**
	 * Sanitize every text section of the plugin information built from GitHub release notes or the readme.
	 *
	 * @param mixed $info Plugin information object from the update checker.
	 * @return mixed
	 */
	public static function clean_info( $info ) {
		if ( ! is_object( $info ) || ! isset( $info->sections ) || ! is_array( $info->sections ) ) {
			return $info;
		}
		$allowed = self::allowed_html();
		foreach ( $info->sections as $name => $html ) {
			if ( is_string( $html ) ) {
				$info->sections[ $name ] = wp_kses( $html, $allowed );
			}
		}
		return $info;
	}

	/**
	 * Expose the underlying checker instance, mostly for debugging.
	 *
	 * @return \YahnisElsts\PluginUpdateChecker\v5\Vcs\Api|null
	 */
	public function checker() {
		return $this->checker;
	}
}
