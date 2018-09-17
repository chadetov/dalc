<?php

	
	$db = new MySQLDalc;
	$tc = new TestClass($db);
	$ulist = $tc->testMethod();

	echo '<pre>';
	print_r($ulist);
	echo '</pre>';


?>