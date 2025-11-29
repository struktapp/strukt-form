<?php

namespace Strukt\Contract;

interface ValidatorInterface{

	public function __construct($value);
	public function getValue();
	public function getMessage():array;
}