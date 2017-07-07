# Monarc Migration Tool

Outils de migrations des données de l'ancienne version à la nouvelle version de Smile/Monarc.

## Installation

	git clone https://github.com/monarc-project/migration_tool.git ./module/MonarcMigrationTool

ou

	via composer

Éditer config/application.config.php et ajouter 'MonarcMigrationTool' à modules.


## Exécution

	php ./public/index.php to see all options


	php ./public/index.php monarc:migrate to execute migration


### Common

	php public/index.php monarc:migrate --mode=common --dbname=<dbname> --password=<password> --host=<host> --user=<user>


### Backoffice / Client

	php public/index.php monarc:migrate --mode=client/backoffice --dbname=<dbname> --password=<password> --host=<host> --user=<user> --dbnamec=<dbname common --passwordc=<password common> --hostc=<host common> --userc=<user common>


## Erreurs

* Erreur d'écriture du cache Doctrine:
	
	chmod -R 777 ./data/DoctrineORMModule

## Conseils

* Utiliser php >= 7: en php 5.6 l'import des liens AMVs (+1900 éléments) est très long
