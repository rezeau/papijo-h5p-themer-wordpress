<?php
/** Read-only legacy refusal. Presence is never interpreted as migrated or disposable.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
final class Legacy_Guard {
	public static function verify($db, $options) {
		if (!is_string($options) || !preg_match('/\A[A-Za-z0-9_]+\z/', $options)) { throw new Storage_Exception('owned_state_legacy_unverified'); }
		try { $rows = State_Schema::rows($db, $db->prepare("SELECT option_name FROM `$options` WHERE option_name IN (%s, %s) LIMIT 2", 'pjh5pwp_theme_library', 'pjh5pwp_role_permissions')); }
		catch (\Throwable $error) { throw new Storage_Exception('owned_state_legacy_unverified'); }
		if ($rows) { throw new Storage_Exception('owned_state_legacy_unsupported'); }
	}
}
