<?php
/** WordPress persistence entry point. Loading definitions does not open a DB. */
require_once __DIR__ . '/Storage/class-storage-exception.php';
require_once __DIR__ . '/Storage/class-snapshot-codec.php';
require_once __DIR__ . '/Storage/interface-migration-step.php';
require_once __DIR__ . '/Storage/class-schema-migrations.php';
require_once __DIR__ . '/Storage/class-wordpress-connections.php';
require_once __DIR__ . '/Storage/class-wordpress-snapshot-store.php';
require_once __DIR__ . '/Storage/class-storage-installation.php';
require_once __DIR__ . '/owned-storage.php';
