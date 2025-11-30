<?php

helper("validator");

alias("provider", Strukt\Contract\ProviderInterface::class);
alias("validator", Strukt\Contract\ValidatorInterface::class);

app("provider", [
	"validator"=>\Strukt\Provider\Validator::class
]);

app("validator", [
	\App\Validator::class,
	\Strukt\Validator::class
]);

if(helper_add("form")){

	/**
	 * Example: 
	 * 	$f = form("au.frm.User", request(["username"=>"pitsolu", "password"=>"p@55w0rd"]));
	 * 	$messages = $f->validate();
	 * 
	 * @param string $which
	 * @param \Strukt\Http\Request $request
	 * 
	 * @return \Strukt\Framework\Contract\Form
	 */
	function form(string $which, Request $request):AbstractForm{

		//
	}
}

if(helper_add("validator")){

	/**
	 * Example: validator("is_len", "strukt", 5)
	 * 
	 * @param string $type - name of validator in slug case e.g is_len 
	 * @param mixed $value - your argument to validate
	 * @param ...$args - validator arguments
	 * 
	 * @return bool
	 */
	function validator(string $type, mixed $value, ...$args):bool{

		$provider = reg("validator");
		if(is_null($provider))
			raise(sprintf("%s not yet registered!", app("provider.validator")));

		$validator = $provider->getNew($value);
		$ref = Strukt\Ref::createFrom($validator);
		$validator = $ref->method(lcfirst(str($type)->toCamel()->yield()))->invoke(...$args);
		$messages = $validator->getMessage();

		return reset($messages);
	}
}