<?php
/** Representative preview adapted from Backdrop v0.5.0 h5p_themer.preview.inc.
 * Original: GPL-2.0-or-later. Adaptation: GPL-3.0-or-later.
 */
namespace Papijo\H5PThemerWordPress\Admin;
use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Theme\Color_Properties;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;

final class Theme_Preview {
	private $core_reader;
	private $supported;
	/** Optional asset reader is a test seam; production always reads the fixed official path. */
	public function __construct( $core_reader = null ) { $this->core_reader = $core_reader; }
	public static function read_css( $path ) {
		try { return is_file( $path ) && is_readable( $path ) ? self::safe_css( @file_get_contents( $path ) ) : null; }
		catch ( \Throwable $error ) { return null; }
	}
	private static function safe_css( $css ) {
		return is_string( $css ) && '' !== trim( $css ) && 1 === preg_match( '//u', $css ) && false === strpos( $css, "\0" ) && false === stripos( $css, '</style' ) ? $css : null;
	}
	private function supported() {
		if ( $this->core_reader ) { return true; }
		if ( null === $this->supported ) {
			$this->supported = false;
			if ( class_exists( '\\Papijo\\H5PThemerWordPress\\Compatibility\\H5P_Dependency' ) ) {
				$dependency = new \Papijo\H5PThemerWordPress\Compatibility\H5P_Dependency( new \Papijo\H5PThemerWordPress\Compatibility\WordPress_Environment() );
				$this->supported = $dependency->inspect()['supported'];
			}
		}
		return $this->supported;
	}
	/** Only approved color properties/values can enter either preview document. */
	public static function colors( array $colors ) {
		$result = array();
		foreach ( Color_Properties::supported() as $property ) {
			$value = $colors[$property] ?? null;
			if ( Color_Properties::valid_hex( $value ) || ( '--h5p-theme-contrast-cta-light' === $property && in_array( $value, array( 'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)', 'rgba(59, 130, 246, 0.15)' ), true ) ) ) { $result[$property] = $value; }
		}
		return $result;
	}
	/** Presentation only: callers authorize and resolve their own selections. No storage reads. */
	public function document( array $colors, $label, $compact = false ) {
		if ( ! $this->supported() ) { return null; }
		$core = $this->core_reader ? self::safe_css( call_user_func( $this->core_reader ) ) : ( defined( 'WP_PLUGIN_DIR' ) ? self::read_css( WP_PLUGIN_DIR . '/h5p/h5p-php-library/styles/h5p-theme-variables.css' ) : null );
		$sample = self::read_css( dirname( __DIR__, 2 ) . '/assets/css/preview.css' );
		if ( null === $core || null === $sample ) { return null; }
		$declarations = '';
		foreach ( self::colors( $colors ) as $property => $value ) { $declarations .= $property . ':' . $value . ';'; }
		$title = __( $compact ? 'Selected Theme preview' : 'Saved theme preview', 'papijo-h5p-themer-wordpress' ) . ': ' . $label;
		$policy = "default-src 'none'; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'";
		return '<!doctype html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="Content-Security-Policy" content="' . esc_attr( $policy ) . '"><title>' . esc_html( $title ) . '</title><style>' . $core . '</style><style>' . $sample . '</style><style id="pjh5pwp-preview-overrides">.pjh5pwp-preview-sample{' . $declarations . '}</style></head><body class="pjh5pwp-preview-document' . ( $compact ? ' pjh5pwp-preview-compact' : '' ) . '"><main class="h5p-content pjh5pwp-preview-sample"><h2>' . esc_html( $label ) . '</h2>' . $this->examples( $compact ) . '</main></body></html>';
	}
	public function render( $reference, array $definitions, $live = false, $comparison = true ) {
		if ( ! Admin_Service::allowed() ) { return ''; }
		// Admission of representative assets is independent of SNORDIAN runtime suspension.
		if ( ! $this->supported() ) { return '<p role="alert">' . esc_html__( 'Preview unavailable: the supported official H5P integration and Core API 1.28 are required.', 'papijo-h5p-themer-wordpress' ) . '</p>'; }
		$resolved = ( new Palette_Resolver() )->resolve( $reference, $definitions );
		$document = $resolved['errors'] ? null : $this->document( $resolved['data']['colors'], $resolved['data']['label'] );
		if ( null === $document ) {
			return '<p role="alert">' . esc_html__( 'Preview unavailable: the saved theme or fixed local H5P preview assets could not be validated. The color fields remain available.', 'papijo-h5p-themer-wordpress' ) . '</p>';
		}
		$theme = $resolved['data'];
		$metadata = '';
		if ( $live && 'custom' === $theme['kind'] ) {
			$definition = $definitions[$theme['id']]; $inherited = array();
			if ( isset( $definition['base_preset'] ) ) {
				$base = $definition['base_preset']; $inherited = ( new Preset_Catalog() )->get( $base['id'], $base['version'] )['data']['colors'];
			}
			$metadata = ' data-pjh5pwp-preview="' . esc_attr( wp_json_encode( array( 'properties' => Color_Properties::supported(), 'saved' => (object) $definition['colors'], 'inherited' => (object) array_intersect_key( $inherited, array_flip( Color_Properties::supported() ) ) ) ) ) . '"';
		}
		$title = __( 'Saved theme preview', 'papijo-h5p-themer-wordpress' ) . ': ' . $theme['label'];
		$html = '<section class="pjh5pwp-saved-preview"' . $metadata . '><h3>' . esc_html__( 'Saved theme preview', 'papijo-h5p-themer-wordpress' ) . '</h3><p>' . esc_html__( 'Representative HTML samples only. H5P libraries and authored styles may look different. Density is not applied. Valid color edits update this preview on custom Edit pages with JavaScript; Save is required to persist them.', 'papijo-h5p-themer-wordpress' ) . '</p><p class="pjh5pwp-preview-status" role="status" aria-live="polite">' . esc_html__( 'Saved colors.', 'papijo-h5p-themer-wordpress' ) . '</p><iframe class="pjh5pwp-preview-frame" title="' . esc_attr( $title ) . '" sandbox="allow-same-origin" height="640" srcdoc="' . esc_attr( $document ) . '"></iframe>';
		if ( $comparison && 'core' !== $theme['kind'] ) { $html .= '<details><summary>' . esc_html__( 'Compare with H5P Core defaults', 'papijo-h5p-themer-wordpress' ) . '</summary>' . $this->render( null, $definitions, false, false ) . '</details>'; }
		return $html . '</section>';
	}
	private function examples( $compact = false ) {
		$t = static function( $text ) { return esc_html( __( $text, 'papijo-h5p-themer-wordpress' ) ); };
		if ( $compact ) {
			return '<p>' . $t('Example text and H5P colors.') . '</p><div class="pjh5pwp-preview-actions"><span class="pjh5pwp-preview-button pjh5pwp-preview-primary">' . $t('Example action') . '</span><span class="pjh5pwp-preview-button pjh5pwp-preview-secondary">' . $t('Alternative action') . '</span></div><p class="pjh5pwp-preview-selected">' . $t('Selected answer (example)') . '</p><div class="pjh5pwp-preview-grid"><p class="pjh5pwp-preview-correct">' . $t('Correct answer (example)') . '</p><p class="pjh5pwp-preview-incorrect">' . $t('Incorrect answer (example)') . '</p></div>';
		}
		return '<p>' . $t('Preview only. Buttons demonstrate appearance and do not perform actions.') . '</p><div class="pjh5pwp-preview-grid"><section class="pjh5pwp-preview-surface"><h3>' . $t('Example heading') . '</h3><p>' . $t('Example body text on a surface.') . '</p><p class="pjh5pwp-preview-secondary-text">' . $t('Secondary text') . '</p><p class="pjh5pwp-preview-muted-text">' . $t('Muted text') . '</p><p class="pjh5pwp-preview-selected">' . $t('Selected answer (example)') . '</p><div class="pjh5pwp-preview-actions"><button type="button" class="pjh5pwp-preview-primary">' . $t('Primary action example') . '</button><button type="button" class="pjh5pwp-preview-secondary">' . $t('Secondary action example') . '</button></div></section><section class="pjh5pwp-preview-surface"><h3>' . $t('Feedback examples') . '</h3><p class="pjh5pwp-preview-correct"><strong>' . $t('Correct:') . '</strong> ' . $t('Example correct feedback.') . '</p><p class="pjh5pwp-preview-incorrect"><strong>' . $t('Incorrect:') . '</strong> ' . $t('Example incorrect feedback.') . '</p><div class="pjh5pwp-preview-progress" role="progressbar" aria-label="' . $t('Example progress') . '" aria-valuemin="0" aria-valuemax="5" aria-valuenow="2">2 / 5</div><ul class="pjh5pwp-preview-borders"><li>' . $t('Border 1') . '</li><li>' . $t('Border 2') . '</li><li>' . $t('Border 3') . '</li></ul></section></div>';
	}
}
