<?php

require "autoload.php";

provider(app("provider.validator"));
$u = new Payroll\AuthModule\Form\UserForm([

	"email"=>"pitsolu@gmail.com",
	"password"=>"p@55w0rd",
	"confirm_password"=>"p@55w0rd"
]);

//dd($u->email);
dd($u->validate());