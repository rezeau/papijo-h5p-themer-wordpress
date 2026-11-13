<?php
/** F1 compatibility alias to the production owned-storage implementation. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Foundation\OwnedStorage;
require_once dirname(__DIR__, 2) . '/includes/wordpress-storage.php';
class_alias('Papijo\\H5PThemerWordPress\\Storage\\Owned\\Theme_Store', __NAMESPACE__ . '\\Theme_Store');
