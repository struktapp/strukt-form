<?php

namespace Strukt\Contract;

use Strukt\Http\Request;

/**
* Form class to be inherited in Form
*
* @author Moderator <pitsolu@gmail.com>
*/
abstract class Form{

	/**
	* Http Request
	*
	* @return Strukt\Http\Request
	*/
	private $request;

	/**
	* Constructor
	*
	* @param \Strukt\Http\Request|Array $request 
	*/
	public function __construct(Request|Array $request){

		$this->request = $request;
	}

	/**
	* Getter raw validator values
	*
	* @param string $key
	*/
	public function get(string $key){

		if(is_array($this->request))
			return $this->request[$key];

		return $this->request->get($key);
	}

	/**
	* Execute validator and return compiled messages
	*
	* @return array
	*/
	public function validate():array{

		$ref = ref(get_called_class());
		$props = arr($ref->getRef()->getProperties())
				->map(fn($k,$v)=>[$v->getName()=>$v->getDocComment()])
				->level(2, noPrefix:true);

		$self = $this;
		$props = arr($props)->each(function($param, $docblock) use($self){

			$value = $self->get($param);
			$block = str(deblock($docblock))->replace(["@"],"");

			$validators = arr($block->split("\n"));
			$validators = $validators->map(function($k,$v) use($self, $value){

				$validator = str($v);
				$params = $validator->btwn("(",")")->yield();
				$params = negate(empty($params))?str($params)->split(","):[];
				
				list($name, $_) = $validator->split("(");
				$name = str($name)->toSnake()->yield();

				/**
				* Allow referencing another field
				* 
				* Example: You have `password` and `confirm_password`
				* you would have to use Strukt\Validator.equalTo
				* on `confirm_password`field annotion in the @Form
				* you'd indicate an annotaion validator like this: @EqualTo(.password)
				*/
				$pvalues = [];
				if(negate(empty($params)))
					$pvalues = arr($params)
								->each(fn($k,$v)=>str($v)
									->startsWith(".")?$self->get(str($v)
									->replace(".","")
									->yield()):$v)
								->yield();

				if(negate(empty($pvalues)))
					return [str($name)
							->concat(sprintf("[%s]", arr($params)->join(",")))
							->yield()=>validator($name, $value, ...$pvalues)];

				return [$name=>validator($name, $value)];
			});

			return $validators->level(2, noPrefix:true);
		});

		return $props->yield();
	}
}