<?php
/** Escaped native forms; no scripts, storage or uploaded-name rendering. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Admin;
final class Transfer_View {
	private $url;
	public function __construct( $url ) { $this->url = $url; }
	private function t( $text ) { return esc_html( __( $text, 'papijo-h5p-themer-wordpress' ) ); }
	private function hidden( $key, $value ) { return '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; }
	private function form( $op, $source, array $state, $extra = '' ) {
		$revision = (string) $state['revision']; $context = Admin_Service::context( $state );
		return '<form method="post" action="' . esc_url( $this->url ) . '" ' . $extra . '>' . $this->hidden( 'pjh5pwp_op', $op ) . $this->hidden( 'revision', $revision ) . $this->hidden( 'context', $context ) . $this->hidden( 'nonce', wp_create_nonce( Transfer_Admin::nonce_action( $op, $source, $revision, $context ) ) );
	}
	public function export_button( $id ) { return ' <button type="submit" class="button-link" form="pjh5pwp-export-' . esc_attr( $id ) . '">' . $this->t( 'Export colors' ) . '</button>'; }
	public function exports( array $state ) {
		$html = '<h2>' . $this->t( 'Export / import theme colors' ) . '</h2><p><a class="button" href="' . esc_url( add_query_arg( array( 'pjh5pwp_view' => 'import' ), $this->url ) ) . '">' . $this->t( 'Import theme colors' ) . '</a></p><p>' . $this->t( 'Export published or unpublished custom themes. Files contain colors and inheritance only; Density, preferences and the global default are excluded.' ) . '</p>';
		if ( ! $state['definitions'] ) { return $html . '<p>' . $this->t( 'There are no custom themes to export.' ) . '</p>'; }
		foreach ( $state['definitions'] as $id => $definition ) {
			// External submit buttons keep these forms outside the visibility form.
			$html .= $this->form( 'export', $id, $state, 'id="pjh5pwp-export-' . esc_attr( $id ) . '"' ) . $this->hidden( 'mode', 'single' ) . $this->hidden( 'id', $id ) . '</form>';
		}
		$html .= $this->form( 'export', 'bulk', $state ) . $this->hidden( 'mode', 'selected' ) . '<fieldset><legend>' . $this->t( 'Select custom themes to export' ) . '</legend>';
		foreach ( $state['definitions'] as $id => $definition ) { $html .= '<p><label><input type="checkbox" name="ids[]" value="' . esc_attr( $id ) . '"> ' . esc_html( $definition['label'] ) . ' <code>' . esc_html( $id ) . '</code></label></p>'; }
		$html .= '</fieldset><p><button type="submit" class="button">' . $this->t( 'Export selected colors' ) . '</button></p></form>';
		return $html . $this->form( 'export', 'bulk', $state ) . $this->hidden( 'mode', 'all' ) . '<p><button type="submit" class="button">' . $this->t( 'Export all custom colors' ) . '</button></p></form>';
	}
	public function imported( $count ) { return '<div class="notice notice-success" role="status"><p>' . esc_html( sprintf( __( 'Imported %d custom theme(s). They remain unpublished. Density and existing preferences are unchanged.', 'papijo-h5p-themer-wordpress' ), $count ) ) . '</p></div>'; }
	public function import_page( array $status, array $result ) {
		$html = '<div class="wrap pjh5pwp-admin"><h1>' . $this->t( 'Import theme colors' ) . '</h1><p><a class="button" href="' . esc_url( $this->url ) . '">' . $this->t( 'Back to theme overview' ) . '</a></p>';
		foreach ( array_unique( array_merge( $status['errors'], $result['errors'] ?? array() ) ) as $code ) { $html .= '<div class="notice notice-error" role="alert"><p>' . esc_html( Admin_Messages::text( $code ) ) . '</p></div>'; }
		$html .= '<p>' . $this->t( 'Import WordPress colors-only JSON or a compatible native Backdrop custom theme. Existing themes will not be overwritten. New themes are unpublished; Density settings and preferences will not change. Backdrop legacy Density metadata is ignored.' ) . '</p>';
		if ( 'ready' !== $status['state'] ) { return $html . '<p>' . $this->t( 'Valid initialized theme storage is required before importing. Return to the overview to review storage status.' ) . '</p></div>'; }
		if ( isset( $result['review'], $result['plan'] ) && empty( $result['errors'] ) ) {
			$review = $result['review']; $plan = $result['plan'];
			$html .= '<h2>' . $this->t( 'Review before importing' ) . '</h2><p>' . esc_html( sprintf( __( '%d theme(s) will be created. Review expires after 15 minutes.', 'papijo-h5p-themer-wordpress' ), count( $plan['records'] ) ) ) . '</p><table class="widefat striped"><thead><tr>';
			foreach ( array( 'Identifier', 'Name', 'Description', 'Base preset', 'Color overrides' ) as $heading ) { $html .= '<th scope="col">' . $this->t( $heading ) . '</th>'; }
			$html .= '</tr></thead><tbody>';
			foreach ( $plan['records'] as $record ) {
				$base = null === $record['base_preset'] ? __( 'H5P Core defaults', 'papijo-h5p-themer-wordpress' ) : $record['base_preset']['id'] . ' @ ' . $record['base_preset']['version'];
				$html .= '<tr><td><code>' . esc_html( $record['id'] ) . '</code></td><td>' . esc_html( $record['label'] ) . '</td><td>' . nl2br( esc_html( $record['description'] ?? '' ) ) . '</td><td>' . esc_html( $base ) . '</td><td>' . esc_html( (string) count( $record['colors'] ) ) . '</td></tr>';
			}
			$html .= '</tbody></table><form method="post" action="' . esc_url( $this->url ) . '">' . $this->hidden( 'pjh5pwp_op', 'import_commit' );
			foreach ( $review as $key => $value ) { $html .= $this->hidden( 'review[' . $key . ']', $value ); }
			$html .= $this->hidden( 'nonce', wp_create_nonce( Transfer_Admin::nonce_action( 'import_commit', $review['signature'], $review['revision'], $review['context'] ) ) );
			return $html . '<p><label><input type="checkbox" name="confirm" value="1" required> ' . $this->t( 'Create all reviewed themes as unpublished custom themes.' ) . '</label></p><p><button type="submit" class="button button-primary">' . $this->t( 'Confirm import' ) . '</button> <a class="button" href="' . esc_url( $this->url ) . '">' . $this->t( 'Cancel' ) . '</a></p></form></div>';
		}
		return $html . $this->form( 'import_preview', '', $status['data'], 'enctype="multipart/form-data"' ) . '<p><label for="pjh5pwp-theme-json">' . $this->t( 'Theme JSON file (maximum 1 MiB, 100 themes)' ) . '</label></p><input type="file" id="pjh5pwp-theme-json" name="theme_json" accept=".json,application/json" required><p><button type="submit" class="button button-primary">' . $this->t( 'Validate and preview' ) . '</button></p></form></div>';
	}
}
