<?php
/** Standard single-site, nonpersistent private WordPress sessions; no Options API.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;
use Papijo\H5PThemerWordPress\Storage\WordPress_Connections;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
final class State_Connections {
	private $wordpress;
	public function __construct() { $this->wordpress = new WordPress_Connections(); }
	public static function table() { $factory = new self(); return new State_Table(array($factory, 'open')); }
	public function open() {
		list($db, $options) = $this->wordpress->open();
		try {
			$thread = $db->thread_id();
			Legacy_Guard::verify($db, $options);
			$table = State_Schema::table_name($GLOBALS['wpdb']->prefix);
			if (!$thread || $thread !== $db->thread_id()) { throw new Storage_Exception('owned_state_legacy_unverified'); }
			return array($db, $table);
		} catch (\Throwable $error) { try { $db->close(); } catch (\Throwable $ignored) {} throw $error; }
	}
}
