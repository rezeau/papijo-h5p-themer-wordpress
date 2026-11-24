<?php
/** Independent site permission. No activation writes or preference storage. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Anonymous;
final class Permission {
    const OPTION = 'pjh5pwp_allow_anonymous_appearance';
    const LABEL = 'Allow anonymous visitors to select their H5P appearance';
    public function enabled() {
        try { return !is_multisite() && in_array(get_option(self::OPTION, false), array(true, '1'), true); }
        catch (\Throwable $error) { return false; }
    }
    public function submit($post) {
        if (is_multisite() || !current_user_can('manage_options')) { return 'forbidden'; }
        if (!is_array($post) || count($post)!==4 || array_diff(array('pjh5pwp_op','anonymous_nonce','allow_anonymous','anonymous_complete'),array_keys($post)) ||
            ($post['pjh5pwp_op']??null)!=='anonymous_permission' || ($post['anonymous_complete']??null)!=='1' ||
            !is_string($post['anonymous_nonce']??null) || !in_array($post['allow_anonymous']??null,array('0','1'),true)) { return 'invalid_submission'; }
        if (!wp_verify_nonce($post['anonymous_nonce'],'pjh5pwp_anonymous_permission')) { return 'invalid_nonce'; }
        $next=$post['allow_anonymous']==='1';
        try {
            if ($this->enabled()===$next) { return 'saved'; }
            return update_option(self::OPTION,$next,false) ? 'saved' : 'save_failed';
        } catch (\Throwable $error) { return 'save_failed'; }
    }
    public function render($url,$result='') {
        if (is_multisite() || !current_user_can('manage_options')) { return ''; }
        $t=static function($s){return esc_html(__($s,'papijo-h5p-themer-wordpress'));};
        $html='<section class="pjh5pwp-admin"><h2>'.$t('Anonymous visitors').'</h2>';
        if ($result!=='') { $html.='<p role="status">'.$t($result==='saved'?'Anonymous permission saved.':'Anonymous permission was not confirmed saved. Reload and review.').'</p>'; }
        return $html.'<form method="post" action="'.esc_url($url).'">'.
            '<input type="hidden" name="pjh5pwp_op" value="anonymous_permission">'.
            '<input type="hidden" name="anonymous_nonce" value="'.esc_attr(wp_create_nonce('pjh5pwp_anonymous_permission')).'">'.
            '<input type="hidden" name="allow_anonymous" value="0"><label><input type="checkbox" name="allow_anonymous" value="1"'.($this->enabled()?' checked':'').'> '.$t(self::LABEL).'</label>'.
            '<p>'.$t('Choices stay in the browser and apply to eligible H5P activities. The selector starts collapsed on each page.').'</p>'.
            '<input type="hidden" name="anonymous_complete" value="1"><p><button class="button button-primary">'.$t('Save anonymous permission').'</button></p></form></section>';
    }
}
