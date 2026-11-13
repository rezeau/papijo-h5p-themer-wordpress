<?php
/** Pure domain entry point: no WordPress APIs, hooks, persistence or runtime CSS. */
require_once __DIR__ . '/Theme/class-color-properties.php';
require_once __DIR__ . '/Theme/class-preset-catalog.php';
require_once __DIR__ . '/Theme/class-theme-validator.php';
require_once __DIR__ . '/Theme/class-palette-resolver.php';
require_once __DIR__ . '/Theme/class-theme-operations.php';
require_once __DIR__ . '/Publication/class-publication-policy.php';
require_once __DIR__ . '/Storage/interface-snapshot-store.php';
require_once __DIR__ . '/Storage/class-schema.php';
require_once __DIR__ . '/Theme/class-library-changes.php';
