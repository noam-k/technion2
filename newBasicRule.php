<?php

	session_start();
	include_once("controller/controller.php");

	$rules = new RulesController();

	if (!empty($_POST)) {
		try {
			$rules->processBasicInput($_POST);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	$rules->renderAddNewBasicRulePage();
