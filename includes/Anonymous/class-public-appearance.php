<?php
/** Read-only publication allowlist and public projection of unchanged main services. */
namespace Papijo\H5PThemerWordPress\Anonymous;


final class Public_Appearance {
    private $library; private $admission;
    public function __construct( callable $library, callable $admission ) { $this->library = $library; $this->admission = $admission; }
    public function resolve( $theme, $density ) {
        $invalid = array( 'appearance' => null, 'selection' => null, 'catalog' => null, 'fallback_reason' => null, 'errors' => array( 'invalid_selection' ) );
        if ( ! \Papijo\H5PThemerWordPress\Publication\Publication_Policy::valid_token( $theme ) || ! in_array( $density, array('original','small','medium','large'), true ) ) { return $invalid; }
        try {
            $state = call_user_func( $this->library );
            if ( ( new \Papijo\H5PThemerWordPress\Storage\Schema() )->validate( $state )['errors'] ) { return $invalid; }
            $policy = new \Papijo\H5PThemerWordPress\Publication\Publication_Policy();
            $allowed = $policy->catalog( $state['definitions'], $state['publication'] );
            if ( $allowed['errors'] || count( $allowed['data'] ) > 64 ) { return $invalid; }
            $themes = array();
            foreach ( $allowed['data'] as $token => $choice ) {
                if ( ! is_string( $choice['label'] ) || strlen( $choice['label'] ) > 160 ) { return $invalid; }
                $themes[] = array( 'value' => $token, 'label' => $choice['label'] );
            }
            $fallback = ! isset( $allowed['data'][$theme] );
            $accepted = $fallback ? 'site_default' : $theme;
            $resolved = $policy->resolve_learner( $accepted, $state['settings']['default_theme'], $state['definitions'], $state['publication'] );
            $service = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Service( $this->admission,
                static function ( $viewer ) use ( $resolved, $density ) {
                    if ( $viewer !== 0 ) { throw new \RuntimeException( 'anonymous_only' ); }
                    return array( 'theme' => $resolved, 'density' => $density );
                } );
            $appearance = $service->describe( 0 );
            if ( ! $appearance['admission']['permitted'] ) { return $invalid; }
            // No identity/label/reference of an inherited unpublished global can leave this boundary.
            $appearance['theme'] = array( 'source' => $appearance['theme']['source'], 'kind' => 'effective', 'id' => null, 'version' => null );
            return array( 'appearance' => $appearance, 'selection' => array( 'theme' => $accepted, 'density' => $density, 'theme_fallback' => $fallback ),
                'catalog' => array( 'themes' => $themes, 'densities' => array( 'original', 'small', 'medium', 'large' ) ),
                'fallback_reason' => $fallback ? 'theme_unavailable' : null, 'errors' => array() );
        } catch ( \Throwable $error ) { return $invalid; }
    }
}
