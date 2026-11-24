<?php
/** Page/player-bound public admission tokens; not account authorization. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Anonymous;
final class Player_Grants {
    public static function player($p) {
        if (!is_array($p) || count($p)!==3 || array_diff(array('content_id','embed','library'),array_keys($p)) ||
            !is_string($p['content_id']) || !preg_match('/\A[1-9][0-9]{0,9}\z/',$p['content_id']) ||
            !in_array($p['embed'],array('iframe','div'),true) || !is_string($p['library']) || strlen($p['library'])>160 ||
            !preg_match('/\AH5P\.[A-Za-z][A-Za-z0-9]* [0-9]{1,4}\.[0-9]{1,4}\z/',$p['library'])) { return null; }
        return array('content_id'=>$p['content_id'],'embed'=>$p['embed'],'library'=>$p['library']);
    }
    public static function page($url) {
        if (!is_string($url) || strlen($url)>2048 || preg_match('/[\x00-\x20\x7f\\\\]/',$url)) { return null; }
        $p=parse_url($url);
        if (!is_array($p) || isset($p['fragment']) || isset($p['user']) || isset($p['pass'])) { return null; }
        return ($p['path']??'/').(isset($p['query'])?'?'.$p['query']:'');
    }
    public static function action($page,array $player) {
        return 'pjh5pwp_anonymous_'.hash('sha256',$page."\n".wp_json_encode(self::player($player)));
    }
    /** Caller supplies only fresh Playback_Registry candidates after frontend admission. */
    public static function issue($page,array $players) {
        if (!is_string($page) || self::page($page)!==$page || !$players || count($players)>64) { return null; }
        $out=array(); $seen=array();
        foreach ($players as $p) {
            $p=self::player($p);
            if (!$p || isset($seen[$p['content_id']])) { return null; }
            $seen[$p['content_id']]=true;
            $out[]=array('player'=>$p,'nonce'=>wp_create_nonce(self::action($page,$p)));
        }
        return $out;
    }
}
