<?php

class sspmod_selfregister_XHTML_Form {

	private $layout = array();
	private $values = array();
	private $toWrite = array();
	private $hidden = array();
	private $readonly = array();
	private $disabled = array();
	private $size = 20;
	private $actionEndpoint = '?';
	private $transAttr = NULL;
	private $transDesc = NULL;
	private $submitName = 'sender';
	private $submitValue = 'Submit';


	public function __construct($fieldsDef = array(), $actionEndpoint = NULL){
		foreach ($fieldsDef as $name => $field) {
			$this->layout[$name] = $field['layout'];
		}
		if($actionEndpoint) $this->actionEndpoint = $actionEndpoint;

		$config = SimpleSAML_Configuration::getInstance();
		$this->transAttr = new SimpleSAML_XHTML_Template(
			$config,
			'selfregister:step1email.php', // Selected as a dummy
			'attributes');
		$this->transDesc = new SimpleSAML_XHTML_Template(
			$config,
			'selfregister:step1email.php', // Selected as a dummy
			'selfregister:selfregister');

	}


	public function setValues($formValues){
		$this->values = array_merge($this->values, $formValues);
	}


	public function setSubmitter($value, $name = 'sender'){
		$this->submitName = $name;
		$this->submitValue = $value;
	}


	public function fieldsToShow($fields){
		$fields = SimpleSAML_Utilities::arrayize($fields);
		$this->toWrite = array_merge($this->toWrite, $fields);
	}


	public function addHiddenData($arrNameValue){
		$this->hidden = array_merge($this->hidden, $arrNameValue);
	}


	/*
	 * String: field name
	 * or arry of fieldnames
	 */
	public function setReadOnly($fields){
		$fields = SimpleSAML_Utilities::arrayize($fields);
		$this->readonly = array_merge($this->readonly, $fields);
	}

	/*
	 * String: field name
	 * or arry of fieldnames
	 */
	public function setDisabled($fields){
		$fields = SimpleSAML_Utilities::arrayize($fields);
		$this->disabled = array_merge($this->disabled, $fields);
	}


	private function writeFormStart(){
		$format = '<form action="%s" method="post">';
		$html = sprintf($format, $this->actionEndpoint);
		return $html;
	}


	private function writeFormEnd(){
		return '</form>';
	}

	private function writeFormElement($elementId){
		$html = '<div class="element">';
		$html .= $this->writeLabel($elementId);
		$html .= $this->writeInputControl($elementId);
		$html .= $this->writeControlDescription($elementId);
		$html .= '</div>';

		return $html;
	}


	private function writeLabel($elementId){
		$format = '<label for="%s">%s</label>';
		$trTag = strtolower('attribute_'.$elementId);
		$trLabel = htmlspecialchars($this->transAttr->t($trTag));
		// Got no translation, try again
		if( (bool)strstr($trLabel, 'not translated') ) {
			$trLabel = htmlspecialchars($this->transDesc->t($elementId));
		}
		$html = sprintf($format, $elementId, $trLabel);
		return $html;
	}


	private function writeInputControl($elementId){
		$value = isset($this->values[$elementId])?$this->values[$elementId]:'';
		$value = htmlspecialchars($value);
		if($this->actionEndpoint != 'delUser.php') {
			$format = '<input class="inputelement" type="%s" id="%s" name="%s" value="%s" size="%s" %s />';
			$attr = '';
			if(in_array($elementId, $this->readonly)){
				$attr .= 'readonly="readonly"';
			}
			if(in_array($elementId, $this->disabled)){
				$attr .= 'disabled="disabled"';
			}
			if(in_array($elementId, $this->disabled)){
				$attr .= 'disabled="disabled"';
			}

			$type = $this->layout[$elementId]['control_type'];
			$html = sprintf($format, $type, $elementId, $elementId, $value, $this->size, $attr);
		}
		else {
			$format = '<br>%s<input type="hidden" id="%s" name="%s" value="%s" >';
			$html = sprintf($format, $value, $elementId, $elementId, $value);
		}

		return $html;
	}


	private function writeControlDescription($elementId) {

		$format = '%s';
		$descId = $elementId.'_desc';
		$trDesc = htmlspecialchars($this->transDesc->t($descId) );
		if($this->actionEndpoint == 'delUser.php' || (bool)strstr($trDesc, 'not translated') ) {
			return '';
		}

		$html = '<p class="elementDescr">' . sprintf($format, $trDesc) . '</p>';
		return $html;
	}


	private function writeHidden(){
		$html = '';
		$format = '<input type="hidden" name="%s" value="%s" />';
		foreach($this->hidden as $name => $value){
			$html .= sprintf($format, $name, htmlspecialchars($value) );
		}

		return $html;
	}


	private function writeFormSubmit(){
		$html = '';
		$format = '<input type="submit" name="%s" value="%s" />';
		$trValue = htmlspecialchars($this->transDesc->t($this->submitValue));
		$html = sprintf($format, $this->submitName, $trValue);
		return $html;
	}


	public function genFormHtml(){
		$html = '';

		$html .= $this->writeFormStart();
		if(count($this->hidden) > 0){
			$html .= $this->writeHidden();
		}
		foreach($this->toWrite as $fId){
			switch ($fId){
			case NULL:
				break;
			default:
				$html .= $this->writeFormElement($fId);
			}
		}
		$html .= $this->writeFormSubmit();
		$html .= $this->writeFormEnd();

		return $html;
	}

  } //end class


?>
