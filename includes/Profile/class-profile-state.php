<?php
/** Pure versioned profile state. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Profile;
final class Profile_State {
	// Version 1 keeps these two flags solely to preserve existing canonical bytes.
	// They have no authorization meaning and no HTTP write path in Phase 2A-R.
	public static function defaults(){return array('allow_theme'=>'0','allow_density'=>'0','theme'=>'site_default','density'=>'original');}
	public static function valid($state){
		if(!is_array($state)||array_keys($state)!==array('schema_version','revision','values')||$state['schema_version']!==1||!is_int($state['revision'])||$state['revision']<0||$state['revision']>=PHP_INT_MAX){return false;}
		$v=$state['values'];if(!is_array($v)||array_keys($v)!==array_keys(self::defaults())){return false;}
		return in_array($v['allow_theme'],array('0','1'),true)&&in_array($v['allow_density'],array('0','1'),true)&&\Papijo\H5PThemerWordPress\Publication\Publication_Policy::valid_token($v['theme'])&&in_array($v['density'],array('original','small','medium','large'),true);
	}
}
