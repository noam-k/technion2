<?php
    include_once 'controller/controller.php';
    $controller = new RulesController();
    # NOTE: admin should only be true if the user has admininstator access. I assume all users with access to the system
    #       are admins, but the application can be extended to normal users too
    $admin = true;
    if ($admin && isset($_GET['deleteRule'])) {
        $controller->deleteRule($_GET['deleteRule'], $_GET['table']);
    }
    $controller->renderManageExistingRules($admin);
