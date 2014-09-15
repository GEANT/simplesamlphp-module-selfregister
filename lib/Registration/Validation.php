<?php

class sspmod_selfregister_Registration_Validation {
	private $validators = NULL;

	public function __construct($fieldsDef, $usedFields) {
		foreach ($usedFields as $field) {
			$this->validators[$field] = $fieldsDef[$field]['validate'];
		}
	}


	public function validateInput(){

		$filtered = filter_input_array(INPUT_POST, $this->validators);
		// FIXME: Write failed validation values to log
		foreach($filtered as $field => $value){
			if(!$value){
				$rawValue = isset($_REQUEST[$field])?$_REQUEST[$field]:NULL;
				if(!$rawValue){
					throw new sspmod_selfregister_Error_UserException(
						'void_value',
						$field,
						'',
						'Validation of user input failed.'
						.' Field:'.$field
						.' is empty');
				}else{
					throw new sspmod_selfregister_Error_UserException(
						'illegale_value',
						$field,
						$rawValue,
						'Validation of user input failed.'
						.' Field:'.$field
						.' Value:'.$rawValue);
				}
			}
		}
		return $filtered;
	}


	public function getRawInput(){
		$input = array();
		foreach($this->validators as $fn => $fv){
			if(isset($_REQUEST[$fn])){
				$input[$fn] = $_REQUEST[$fn];
			}
		}
		return $input;
	}

} // end Validation


?>