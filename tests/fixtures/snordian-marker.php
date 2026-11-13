<?php
/** Test-only namespace marker, with no upstream implementation. */
namespace Snordian\H5PThemer;
function h5p_themer_alter_scripts() { throw new \RuntimeException( 'A foreign callback must never be invoked' ); }
