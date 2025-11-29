<?php

namespace Strukt;

class Validator implements Contract\ValidatorInterface{

	use \Strukt\Traits\Validator;

	private $value;
	private $message;
	public function __construct($value){

		$this->value = $value;
	}

	public function getValue(){

		return $this->value;
	}

	public function getMessage():array{

		return $this->message;
	}
};