<?php

namespace Strukt\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Error\BadRequest;
use Strukt\Cmd;
use Strukt\Http\Response\Plain as PlainResponse;

/**
* @Name(valid)
*/
class Validator implements MiddlewareInterface{

	public function __construct(){

		//
	}

		/**
	* @param \Strukt\Contract\Http\ResponseInterface $request
	* @param \Strukt\Contract\Http\ResponseInterface $response
	* @param callable $next
	*
	* @return \Strukt\Http\Response\Plain
	*/
	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, 
								callable $next):PlainResponse{

		$method = $request->getMethod(); 
		if($method == "OPTIONS" &&  config("app.type") == "App:Idx"){

			$body = json($request->getContent())->decode();
			foreach($body as $name=>$val)
				$request->request->set($name, $val);
		}

		$class = reg(sprintf("nr.%s.frm.%s", $tokq->get("module"), $tokq->get("form")));

		$messages = \Strukt\Ref::create($class)->makeArgs([$request])->method("validate")->invoke();
		if(!$messages["success"])
			$response = new Raise("Bad Request", 400);
			
	
		return $next($request, $response);
}