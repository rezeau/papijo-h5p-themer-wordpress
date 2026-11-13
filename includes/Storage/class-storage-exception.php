<?php
/** @package Papijo\H5PThemerWordPress @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Storage;

/** Constant diagnostic codes; never expose credentials, SQL or stored values. */
final class Storage_Exception extends \RuntimeException {
	private $outcome;
	public function __construct( $code, $outcome = 'not_committed' ) {
		parent::__construct( $code );
		$this->outcome = $outcome;
	}
	public function outcome() { return $this->outcome; }
}
