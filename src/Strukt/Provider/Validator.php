<?php

namespace Strukt\Provider;

use Strukt\Contract\ProviderInterface;

/**
* @Name(valid)
* @Required()
*/
class Validator implements ProviderInterface{

	public function __construct(){

		//
	}

	/**
	 * @return void
	 */
	public function register():void{		

		reg("validator", new class{

		    public function getNew(?string $value = null) {

		    	return ref(app("validator"))->makeArgs([$value])->getInstance();
		    }
		});
	}
}
