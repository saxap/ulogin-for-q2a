<?php

/*
	Question2Answer 1.6 (c) 2013, m1stique (http://dontforget.pro)

	http://www.question2answer.org/


	File: qa-plugin/ulogin-login/qa-ulogin-login.php
	Version: 1.0
	Date: 2013-08-28
	Description: Initiates Ulogin login plugin



*/

/*
	Plugin Name: Ulogin Login
	Plugin URI: http://dontforget.pro
	Plugin Description: Allows users to log in via Ulogin service
	Plugin Version: 1.0
	Plugin Date: 2013-08-28
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_module('login', 'qa-ulogin-login.php', 'qa_ulogin_login', 'ulogin');


/*
	Omit PHP closing tag to help avoid accidental output
*/
