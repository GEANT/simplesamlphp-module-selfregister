<?php

  /* This is exceptions most probable caused by a user mistake.
   * Shoud be able to recover if the user is given proper information and the
   * chance to try again.
   */
class sspmod_selfregister_Error_UserException extends Exception {

	protected $umi;
	protected $var1 = NULL;
	protected $var2 = NULL;

	public function __construct(
		$userMessgId,
		$var1 = NULL,
		$var2 = NULL,
		$logMessage = '',
		$errorCode = 0)
	{
		parent::__construct($logMessage, $errorCode);
		$this->umi = $userMessgId;
		$this->var1 = $var1;
		$this->var2 = $var2;
	}

	public function getMesgId(){
		return $this->umi;
	}

	public function getTrVars(){
		$vars = array();
		if($this->var1){
			$vars['%VAR1%'] = $this->var1;
		}
		if($this->var2){
			$vars['%VAR2%'] = $this->var2;
		}
		return $vars;
	}
}

?>