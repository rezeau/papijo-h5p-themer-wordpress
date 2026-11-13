<?php
/** Canonical sparse role grants; display labels are never persisted. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Role;
final class Role_State {
	const OPTION = 'pjh5pwp_role_permissions';
	public static function slug($slug) {
		return is_string($slug) && strlen($slug)>0 && strlen($slug)<=191 && !preg_match('/[\x00-\x1f\x7f]/',$slug);
	}
	public static function valid($state) {
		if(!is_array($state)||array_keys($state)!==array('schema_version','revision','roles')||$state['schema_version']!==1||!is_int($state['revision'])||$state['revision']<0||$state['revision']>=PHP_INT_MAX||!is_array($state['roles'])){return false;}
		foreach($state['roles'] as $slug=>$grant){if(!self::slug($slug)||$grant!==true){return false;}}
		$sorted=$state['roles'];ksort($sorted,SORT_STRING);return $sorted===$state['roles'];
	}
}
