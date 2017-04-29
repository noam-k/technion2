<?php
	session_start();
	include_once("controller/controller.php");
	#echo '<pre>';

	$rules = new RulesController();

	if (!empty($_POST)) {
		try {
			$rules->processFlexibleInput($_POST);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	$rules->renderAddNewFlexibleRulePage();
