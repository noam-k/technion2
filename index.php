<?php
	include_once("controller/controller.php");
	$controller = new RulesController();
	$controller->renderManageExistingRules(true);
