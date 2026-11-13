<?php
/**
 * Core 1.28 property definitions adapted from Backdrop v0.5.0.
 * Original grants: GPL-2.0-or-later. This adaptation: GPL-3.0-or-later.
 * Core property source: 2aeb0b83fa603e331381b3a6b8bf42c3773ba140.
 * @package Papijo\H5PThemerWordPress
 */
namespace Papijo\H5PThemerWordPress\Theme;

/** Fixed color data, never a runtime-derived or arbitrary CSS allowlist. */
final class Color_Properties {
    public static function supported() {
        return array(
    '--h5p-theme-main-cta-base',
    '--h5p-theme-secondary-cta-base',
    '--h5p-theme-alternative-base',
    '--h5p-theme-background',
    '--h5p-theme-focus',
    '--h5p-theme-main-cta-light',
    '--h5p-theme-main-cta-dark',
    '--h5p-theme-contrast-cta',
    '--h5p-theme-contrast-cta-white',
    '--h5p-theme-contrast-cta-light',
    '--h5p-theme-secondary-cta-light',
    '--h5p-theme-secondary-cta-dark',
    '--h5p-theme-secondary-contrast-cta',
    '--h5p-theme-secondary-contrast-cta-hover',
    '--h5p-theme-alternative-light',
    '--h5p-theme-alternative-dark',
    '--h5p-theme-alternative-darker',
    '--h5p-theme-text-primary',
    '--h5p-theme-text-secondary',
    '--h5p-theme-text-third',
    '--h5p-theme-ui-base',
    '--h5p-theme-stroke-1',
    '--h5p-theme-stroke-2',
    '--h5p-theme-stroke-3',
    '--h5p-theme-feedback-correct-main',
    '--h5p-theme-feedback-correct-secondary',
    '--h5p-theme-feedback-correct-third',
    '--h5p-theme-feedback-incorrect-main',
    '--h5p-theme-feedback-incorrect-secondary',
    '--h5p-theme-feedback-incorrect-third',
    '--h5p-theme-feedback-neutral-main',
    '--h5p-theme-feedback-neutral-secondary',
    '--h5p-theme-feedback-neutral-third',
  );
    }

    /** Presentation definitions only. UI adapters translate these source labels. */
    public static function groups() {
        return array(
    'main' => array('title' => 'Main controls', 'fields' => array(
      'main-cta-base' => 'Main control base', 'main-cta-light' => 'Main control light', 'main-cta-dark' => 'Main control dark',
      'contrast-cta' => 'Main control contrast', 'contrast-cta-white' => 'Contrast on white', 'contrast-cta-light' => 'Light contrast surface', 'focus' => 'Focus (not currently used by Core)',
    )),
    'secondary' => array('title' => 'Secondary controls', 'fields' => array(
      'secondary-cta-base' => 'Secondary control base', 'secondary-cta-light' => 'Secondary control light', 'secondary-cta-dark' => 'Secondary control dark',
      'secondary-contrast-cta' => 'Secondary control contrast', 'secondary-contrast-cta-hover' => 'Secondary contrast on hover',
    )),
    'surfaces' => array('title' => 'Background and surfaces', 'fields' => array(
      'background' => 'Content background', 'ui-base' => 'Interface base', 'alternative-base' => 'Alternative base',
      'alternative-light' => 'Alternative light', 'alternative-dark' => 'Alternative dark', 'alternative-darker' => 'Alternative darker',
    )),
    'text' => array('title' => 'Text and borders', 'fields' => array(
      'text-primary' => 'Primary text', 'text-secondary' => 'Secondary text', 'text-third' => 'Third text',
      'stroke-1' => 'Border 1', 'stroke-2' => 'Border 2', 'stroke-3' => 'Border 3',
    )),
    'feedback' => array('title' => 'Feedback colors', 'fields' => array(
      'feedback-correct-main' => 'Correct: main', 'feedback-correct-secondary' => 'Correct: secondary', 'feedback-correct-third' => 'Correct: third',
      'feedback-incorrect-main' => 'Incorrect: main', 'feedback-incorrect-secondary' => 'Incorrect: secondary', 'feedback-incorrect-third' => 'Incorrect: third',
      'feedback-neutral-main' => 'Neutral: main', 'feedback-neutral-secondary' => 'Neutral: secondary', 'feedback-neutral-third' => 'Neutral: third',
    )),
  );
    }

    public static function valid_hex( $value ) {
        return is_string( $value ) && 1 === preg_match( '/\A#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})\z/', $value );
    }
}
