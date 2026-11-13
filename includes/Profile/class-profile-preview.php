<?php
/** Read-only presentation from the already-authorized Profile rendering context. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Profile;

use Papijo\H5PThemerWordPress\Admin\Theme_Preview;
use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Theme\Color_Properties;

final class Profile_Preview {
	private $renderer;
	private $snordian;
	public function __construct( ?Theme_Preview $renderer = null, ?callable $snordian = null ) {
		$this->renderer = $renderer ?: new Theme_Preview();
		$this->snordian = $snordian ?: static function () {
			if ( ! class_exists( '\\Papijo\\H5PThemerWordPress\\Compatibility\\Snordian_Compatibility' ) ) { return false; }
			return ( new \Papijo\H5PThemerWordPress\Compatibility\Snordian_Compatibility( new \Papijo\H5PThemerWordPress\Compatibility\WordPress_Environment() ) )->inspect()['active'];
		};
	}
	private function t( $text ) { return __( $text, 'papijo-h5p-themer-wordpress' ); }
	/** Call only after the controller's own-user/role/storage guards. Never send definitions to JS. */
	public function render( $state, array $choices, $selected ) {
		$resolver = new Palette_Resolver();
		$definitions = $state['definitions'] ?? array();
		$themes = array();
		foreach ( $choices as $token => $choice ) {
			$reference = 'site_default' === $token ? ( $state['settings']['default_theme'] ?? null ) : $choice['reference'];
			$resolved = $resolver->resolve( $reference, $definitions );
			if ( $resolved['errors'] && 'site_default' === $token ) { $resolved = $resolver->resolve( null ); }
			if ( $resolved['errors'] ) { continue; }
			// Even an unpublished global contributes colors only, never its identity/definition.
			$label = $this->t( 'site_default' === $token ? 'Site default' : $choice['label'] );
			$themes[$token] = array( 'label' => $label, 'colors' => (object) Theme_Preview::colors( $resolved['data']['colors'] ) );
		}
		if ( 'keep_saved' === $selected && isset( $themes['site_default'] ) ) {
			$themes['keep_saved'] = array( 'label' => $this->t('Saved preference unavailable; previewing site default'), 'colors' => $themes['site_default']['colors'] );
		}
		$summary = '<details class="pjh5pwp-profile-preview"><summary>' . esc_html( $this->t('Preview selected Theme') ) . '</summary>';
		$unavailable = $this->t('Theme preview unavailable. Your preference controls remain available.');
		if ( ! isset( $themes[$selected] ) ) { return $summary . '<p>' . esc_html( $unavailable ) . '</p></details>'; }
		$initial = $themes[$selected];
		$document = $this->renderer->document( (array) $initial['colors'], $initial['label'], true );
		if ( null === $document ) { return $summary . '<p>' . esc_html( $unavailable ) . '</p></details>'; }
		$messages = array(
			'current' => $this->t('Preview of the current selection. Update Profile is required to save changes.'),
			'unsaved' => $this->t('Preview of an unsaved selection. Update Profile is required to save changes.'),
			'unavailable' => $unavailable,
			'title' => $this->t('Selected Theme preview'),
		);
		$data = array( 'properties' => Color_Properties::supported(), 'themes' => $themes, 'messages' => $messages );
		$json = wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		if ( ! is_string( $json ) ) { return $summary . '<p>' . esc_html( $unavailable ) . '</p></details>'; }
		$html = $summary . '<p>' . esc_html( $this->t('Representative colors only; activities may look different. Density is not applied.') ) . '</p>';
		try { $suspended = (bool) call_user_func( $this->snordian ); } catch ( \Throwable $error ) { $suspended = false; }
		if ( $suspended ) { $html .= '<p>' . esc_html( $this->t("SNORDIAN controls activity appearance while active. This isolated sample shows Papi Jo colors only.") ) . '</p>'; }
		$html .= '<div data-pjh5pwp-profile-preview="' . esc_attr( $json ) . '"><p class="pjh5pwp-profile-preview-status" role="status" aria-live="polite" aria-atomic="true">' . esc_html( $messages['current'] ) . '</p>';
		$html .= '<iframe title="' . esc_attr( $messages['title'] . ': ' . $initial['label'] ) . '" sandbox="allow-same-origin" height="280" srcdoc="' . esc_attr( $document ) . '"></iframe></div>';
		return $html . '<noscript><p>' . esc_html( $this->t('With JavaScript disabled, this preview shows the selection at page load.') ) . '</p></noscript></details>';
	}
}
