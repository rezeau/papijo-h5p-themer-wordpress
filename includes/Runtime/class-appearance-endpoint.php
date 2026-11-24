<?php
/** Read-only session-derived transport. No persistent/page/descriptor cache. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;

final class Appearance_Endpoint {

	const ACTION = 'pjh5pwp_appearance';
	private $appearance;
	private $viewer;
	public function __construct( Appearance_Service $appearance, callable $viewer ) {
		$this->appearance = $appearance;
		$this->viewer = $viewer;
	}
	public function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
		// admin-ajax sends default CORS headers before dispatch, and exits early on OPTIONS.
		add_filter( 'allowed_http_origins', array( $this, 'cors_origins' ), PHP_INT_MAX );
		add_action( 'init', array( $this, 'guard' ), 0 );
	}
	private function targeted() {
		return wp_doing_ajax() && ( self::ACTION === ( $_GET['action'] ?? null ) ||
			self::ACTION === ( $_POST['action'] ?? null ) || self::ACTION === ( $_REQUEST['action'] ?? null ) );
	}
	public function cors_origins( $origins ) { return $this->targeted() ? array() : $origins; }
	public function guard() {
		if ( ! $this->targeted() ) { return; }
		$this->send_headers();
		$error = $this->request_error( $_SERVER, $_GET, $_POST, home_url( '/' ), admin_url() );
		if ( null !== $error ) { $this->send( $this->response( $error[0], $this->suspended( $error[1] ), null ) ); }
	}

	/** Testable HTTP boundary. Identity callback is trusted composition, never request data. */
	public function respond( array $server, array $get, array $post, $home, $admin ) {
		$error = $this->request_error( $server, $get, $post, $home, $admin );
		if ( null !== $error ) { return $this->response( $error[0], $this->suspended( $error[1] ), null ); }
		try {
			$viewer=call_user_func($this->viewer);
			if (isset($post['expected_audience']) && $post['expected_audience']!==($viewer===0?'default':'authenticated')) {
				return $this->response(409,$this->suspended('authentication_changed'),$post['request_id']);
			}
			$descriptor = $this->appearance->describe($viewer);
			if ($viewer===0 && true===($descriptor['admission']['permitted']??null)) {
				$descriptor['theme']=array('source'=>$descriptor['theme']['source'],'kind'=>'custom','id'=>'effective','version'=>null);
			}
		}
		catch ( \Throwable $error ) { $descriptor = $this->suspended( 'appearance_unavailable' ); }
		return $this->response( 200, $descriptor, $post['request_id'] );
	}
	private function request_error( array $server, array $get, array $post, $home, $admin ) {
		if ( 'POST' !== ( $server['REQUEST_METHOD'] ?? null ) ) { return array( 405, 'method_not_allowed' ); }
		if ( ! WordPress_Context::same_origin_request( $server, $home, $admin ) ) { return array( 403, 'unsupported_context' ); }
		$frontend = WordPress_Context::request_allowed( $server, $home, $admin );
		if ( ! $frontend && ! WordPress_Context::admin_transport_allowed( $server, $post, $admin ) ) { return array( 403, 'unsupported_context' ); }
		if (isset($post['expected_audience']) && !in_array($post['expected_audience'],array('authenticated','default'),true)) { return array(400,'invalid_request'); }
		// Exact schema rejects user_id, theme, density, CSS, nonce and unknown/forged fields.
		if ( $get || count( $post ) !== ( $frontend ? (isset($post['expected_audience'])?3:2) : 5 ) || self::ACTION !== ( $post['action'] ?? null ) ||
			! is_string( $post['request_id'] ?? null ) || ! preg_match( '/\A[a-f0-9]{32}\z/', $post['request_id'] ) ) {
			return array( 400, 'invalid_request' );
		}
		return null;
	}
	private function response( $status, array $descriptor, $request_id ) {
		return array( 'status' => $status, 'headers' => self::headers(), 'body' => array(
			'version' => 1, 'request_id' => $request_id, 'refresh' => array( 'max_age_seconds' => 30, 'reuse' => false ),
			'appearance' => $descriptor,
		) );
	}
	private function suspended( $reason ) {
		return array( 'admission' => array( 'permitted' => false, 'reason' => $reason ),
			'theme' => null, 'color_declarations' => '', 'density' => null );
	}
	public static function headers() {
		return array( 'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
			'CDN-Cache-Control' => 'no-store', 'Surrogate-Control' => 'no-store', 'Pragma' => 'no-cache',
			'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT', 'Vary' => 'Cookie, Origin',
			'X-Content-Type-Options' => 'nosniff', 'Cross-Origin-Resource-Policy' => 'same-origin',
			'Content-Type' => 'application/json; charset=UTF-8' );
	}
	private function send_headers() {
		nocache_headers();
		foreach ( array( 'Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials', 'Access-Control-Allow-Headers',
			'Access-Control-Allow-Methods', 'Access-Control-Expose-Headers', 'Access-Control-Max-Age', 'ETag', 'Last-Modified' ) as $name ) { header_remove( $name ); }
		foreach ( self::headers() as $name => $value ) { header( $name . ': ' . $value ); }
	}
	private function send( array $response ) {
		$this->send_headers();
		if ( 405 === $response['status'] ) { header( 'Allow: POST' ); }
		wp_send_json( $response['body'], $response['status'] );
	}
	public function handle() {
		$this->send( $this->respond( $_SERVER, $_GET, $_POST, home_url( '/' ), admin_url() ) );
	}
}
