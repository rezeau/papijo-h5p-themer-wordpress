<?php
/** F1 definitions only: explicit opt-in, no hooks, connection, migration or cutover.
 * @license GPL-3.0-or-later
 */
require_once dirname(__DIR__, 2) . '/includes/theme-domain.php';
require_once dirname(__DIR__, 2) . '/includes/wordpress-storage.php';
require_once dirname(__DIR__, 2) . '/includes/roles.php';
require_once __DIR__ . '/class-state-schema.php';
require_once __DIR__ . '/class-state-table.php';
require_once __DIR__ . '/class-theme-store.php';
require_once __DIR__ . '/class-role-store.php';
require_once __DIR__ . '/class-disposable-installation.php';
