<?php
/** One delivery decision; anonymous selection requires explicit site permission. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;
final class Delivery_Mode {
    public static function select($viewer, $permission) {
        if (!is_int($viewer) || $viewer<0) { return 'none'; }
        return $viewer>0 ? 'authenticated' : ($permission===true ? 'anonymous' : 'default');
    }
}
