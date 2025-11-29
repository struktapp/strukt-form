<?php

require "autoload.php";


$u = new Payroll\AuthModule\Form\UserForm([

	"email"=>"pitsolu@gmail.com",
	"password"=>"p@55w0rd",
	"confirm_password"=>"p@55w0rd"
]);

dd($u->validate());