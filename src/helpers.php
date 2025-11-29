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

		$alias = new class($which){

			use Strukt\Traits\FacetHelper;

			private $which;

			/**
			 * @param string $which
			 */
			public function __construct(string $which){

				$this->which = $which;
			}

			/**
			 * @return string
			 */
			public function valid():string{

				$module_alias = null;
				$class_name = null;
				$facet_alias = null;
				$which = null;

				$qualified = $this->isQualifiedAlias($this->which);

				if($qualified){

					list($_, $facet_alias, $_) = str($this->which)->split(".");
					if(str($facet_alias)->equals("frm"))
						return $this->which;

					raise(sprintf("Invalid form[%s]!", $this->which));
				}

				if(negate($qualified))
					if(preg_match("/^[a-z]{2}\.\w+$/", $this->which))
						list($module_alias, $class_name) = str($this->which)->split(".");

				if(notnull($module_alias) && notnull($class_name))
					$which = str($module_alias)
						->concat(str("frm")->prepend("."))
						->concat(str($class_name)->prepend("."))
						->yield();

				if(is_null($which))
					raise(sprintf("Invalid form[%s]!", $this->which));

				return $which;
			}
		};

		return core($alias->valid(), [$request]);
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