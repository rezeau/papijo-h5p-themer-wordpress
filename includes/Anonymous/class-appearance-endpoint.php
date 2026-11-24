<?php
/** Anonymous-only read transport. No profile access or persistence. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Anonymous;
use Papijo\H5PThemerWordPress\Runtime\WordPress_Context;
final class Appearance_Endpoint {
    const ACTION='pjh5pwp_anonymous_appearance';
    private $public; private $permission; private $runtime; private $viewer;
    public function __construct(Public_Appearance $public,callable $permission,callable $runtime,callable $viewer) {
        $this->public=$public; $this->permission=$permission; $this->runtime=$runtime; $this->viewer=$viewer;
    }
    public function register() {
        add_action('wp_ajax_'.self::ACTION,array($this,'handle'));
        add_action('wp_ajax_nopriv_'.self::ACTION,array($this,'handle'));
        add_action('init',array($this,'guard'),0);
        add_filter('allowed_http_origins',array($this,'cors_origins'),PHP_INT_MAX);
    }
    private function targeted() {
        return wp_doing_ajax() && (($_GET['action']??null)===self::ACTION || ($_POST['action']??null)===self::ACTION || ($_REQUEST['action']??null)===self::ACTION);
    }
    public function cors_origins($origins) { return $this->targeted()?array():$origins; }
    public function guard() {
        if (!$this->targeted()) { return; }
        $this->headers();
        if (($_SERVER['REQUEST_METHOD']??null)!=='POST') { $this->send($this->response(405,null,'method_not_allowed')); }
    }
    public function respond(array $server,array $get,array $post,$home,$admin) {
        $id=is_string($post['request_id']??null) && preg_match('/\A[a-f0-9]{32}\z/',$post['request_id']) ? $post['request_id']:null;
        try {
            if (($server['REQUEST_METHOD']??null)!=='POST') { return $this->response(405,$id,'method_not_allowed'); }
            if (!WordPress_Context::request_allowed($server,$home,$admin)) { return $this->response(403,$id,'unsupported_context'); }
            $fields=array('action','request_id','player','nonce','theme','density');
            $claim=$post['player']??null;
            $player=is_string($claim) && strlen($claim)<=512 ? Player_Grants::player(json_decode($claim,true)):null;
            $page=Player_Grants::page($server['HTTP_REFERER']??null);
            if ($get || count($post)!==count($fields) || array_diff($fields,array_keys($post)) || !$id || !$player || !$page ||
                ($post['action']??null)!==self::ACTION || !is_string($post['nonce']??null) || !preg_match('/\A[a-f0-9]{10}\z/',$post['nonce']) ||
                !\Papijo\H5PThemerWordPress\Publication\Publication_Policy::valid_token($post['theme']??null) ||
                !in_array($post['density']??null,array('original','small','medium','large'),true) ||
                !preg_match('~\Aapplication/x-www-form-urlencoded(?:;|\z)~i',$server['CONTENT_TYPE']??'')) { return $this->response(400,$id,'invalid_request'); }
            $viewer=call_user_func($this->viewer);
            if (!is_int($viewer) || $viewer<0) { return $this->response(403,$id,'identity_unavailable'); }
            if ($viewer>0) { return $this->response(409,$id,'authenticated_session'); }
            if (true!==call_user_func($this->permission)) { return $this->response(403,$id,'anonymous_disabled'); }
            if (!wp_verify_nonce($post['nonce'],Player_Grants::action($page,$player))) { return $this->response(403,$id,'invalid_nonce'); }
            if (true!==call_user_func($this->runtime)) { return $this->response(403,$id,'runtime_unavailable'); }
            $result=$this->public->resolve($post['theme'],$post['density']);
            return $result['errors'] ? $this->response(403,$id,'appearance_unavailable') : $this->response(200,$id,null,$result);
        } catch (\Throwable $error) { return $this->response(403,$id,'appearance_unavailable'); }
    }
    private function response($status,$id,$reason,$result=null) {
        $body=array('version'=>1,'request_id'=>$id,'audience'=>$reason==='authenticated_session'?'authenticated':'anonymous',
            'reload_required'=>$reason==='authenticated_session','reason'=>$reason,'refresh'=>array('max_age_seconds'=>30,'reuse'=>false),
            'appearance'=>$result['appearance']??null,'selection'=>$result['selection']??null,'catalog'=>$result['catalog']??null,'fallback_reason'=>$result['fallback_reason']??null);
        if (strlen((string)wp_json_encode($body))>16384) { return $this->response(403,$id,'appearance_unavailable'); }
        return array('status'=>$status,'headers'=>\Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint::headers(),'body'=>$body);
    }
    private function headers() {
        nocache_headers();
        foreach (array('Access-Control-Allow-Origin','Access-Control-Allow-Credentials','Access-Control-Allow-Headers','Access-Control-Allow-Methods','Access-Control-Expose-Headers','Access-Control-Max-Age','ETag','Last-Modified') as $h) { header_remove($h); }
        foreach (\Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint::headers() as $h=>$v) { header($h.': '.$v); }
    }
    private function send(array $r) { $this->headers(); if ($r['status']===405) { header('Allow: POST'); } wp_send_json($r['body'],$r['status']); }
    public function handle() { $this->send($this->respond($_SERVER,wp_unslash($_GET),wp_unslash($_POST),home_url('/'),admin_url())); }
}
