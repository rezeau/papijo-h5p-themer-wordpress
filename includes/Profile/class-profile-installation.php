<?php
/** Explicit Profile storage lifecycle; construction and ordinary requests are inert.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Profile;

use Papijo\H5PThemerWordPress\Storage\Storage_Exception;

final class Profile_Installation {
	private $store;
	public function __construct(Profile_Store $store){$this->store=$store;}

	public function status(){return $this->run(false);}
	public function ensure_installed(){return $this->run(true);}

	/** Deliberate future migration boundary. Never calls installation or changes data. */
	public function upgrade($target_version){
		return $this->result('unsupported_upgrade',array('profile_upgrade_not_supported'),'not_attempted');
	}

	private function run($install){
		try{
			$state=$install?$this->store->install():$this->store->installation_status();
			return $this->result($state,array(),$install?'confirmed':'not_attempted');
		}catch(\Throwable $error){
			// Never expose exception text, SQL, credentials, table names or stored state.
			$codes=array(
				'profile_engine_unsupported'=>'incompatible',
				'profile_schema_unsupported'=>'incompatible',
				'profile_schema_version_unsupported'=>'incompatible',
				'profile_installation_required'=>'unavailable',
				'profile_metadata_unavailable'=>'unavailable',
				'profile_database_failed'=>'unavailable',
				'profile_environment_unsupported'=>'unavailable',
				'profile_innodb_unavailable'=>'unavailable',
				'profile_installation_denied'=>'failed',
				'profile_installation_failed'=>'failed',
				'profile_installation_unknown'=>'unknown',
				'profile_installation_verification_failed'=>'unknown',
			);
			$code=$error instanceof Storage_Exception&&isset($codes[$error->getMessage()])?$error->getMessage():'profile_storage_unavailable';
			$unknown=$error instanceof Storage_Exception&&$error->outcome()==='unknown';
			return $this->result($unknown?'unknown':($codes[$code]??'unavailable'),array($code),$unknown?'unknown':'not_committed');
		}
	}
	private function result($state,array $errors,$outcome){return array('state'=>$state,'errors'=>$errors,'outcome'=>$outcome);}
}
