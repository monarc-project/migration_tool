<?php
namespace MonarcMigrationTool;

return array(
    'router' => array(
        'routes' => array(
            // Don't use it
        ),
    ),
    "console" => array(
        "router" => array(
            "routes" => array(
                "migration-monarc" => array(
                    //'type' => 'catchall',
                    "options" => array(
                        "route" => "monarc:migrate [--debug|-d] [--yes|-y] [--mode=] [--dbname=] [--host=] [--user=] [--password=] [--dbnamec=] [--hostc=] [--userc=] [--passwordc=]",
                        "defaults" => array(
                            "controller" => Controller\MigrateController::class,
                            "action" => "index", // show-user => showUsersAction()
                        ),
                    ),
                ),
            ),
        ),
    ),
    "controllers" => array(
        'factories' => array(
            Controller\MigrateController::class => Controller\MigrateControllerFactory::class,
        ),
        'invokables' => array(
        ),
    ),
    "service_manager" => array(
        "factories" => array(
            Service\MigrateService::class => Service\MigrateServiceFactory::class,
        ),
        'invokables' => array(
        ),
    ),
);
