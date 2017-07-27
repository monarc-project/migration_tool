# Monarc Migration Tool

Migration's tool for the old monarc customers to the current version

## Installation

	git clone https://github.com/monarc-project/migration_tool.git ./module/MonarcMigrationTool


## Execution

	php ./public/index.php to see all options


	php ./public/index.php monarc:migrate to execute migration


### Common

	php public/index.php monarc:migrate --mode=common --dbname=<dbname> --password=<password> --host=<host> --user=<user>


### Backoffice / Client

	php public/index.php monarc:migrate --mode=client/backoffice --dbname=<dbname> --password=<password> --host=<host> --user=<user> --dbnamec=<dbname common --passwordc=<password common> --hostc=<host common> --userc=<user common>


## Erreurs

Some Client's DB can have issue of integrity preventing the completness of the migration. The following request correct the bug. It must be done before the migration on the client's DB

	update dims_mod_smile_anr_recommandations_risks

	set dims_mod_smile_anr_recommandations_risks.a_id = (SELECT dims_mod_smile_anr_qualif.id_asset
	from dims_mod_smile_anr_qualif 
	where dims_mod_smile_anr_recommandations_risks.risk_id = dims_mod_smile_anr_qualif.id),

	dims_mod_smile_anr_recommandations_risks.v_id = (SELECT dims_mod_smile_anr_qualif.id_vul
	from dims_mod_smile_anr_qualif
	where dims_mod_smile_anr_recommandations_risks.risk_id = dims_mod_smile_anr_qualif.id),

	dims_mod_smile_anr_recommandations_risks.m_id = (SELECT dims_mod_smile_anr_qualif.id_menace
	from dims_mod_smile_anr_qualif 
	where dims_mod_smile_anr_recommandations_risks.risk_id = dims_mod_smile_anr_qualif.id),

	where dims_mod_smile_anr_recommandations_risks.a_id = 0 and dims_mod_smile_anr_recommandations_risks.v_id = 0 and 		dims_mod_smile_anr_recommandations_risks.m_id = 0 
	and dims_mod_smile_anr_recommandations_risks.biblio_global_id = 0 ; 
	

Writing error in Doctrine cache :
	
	chmod -R 777 ./data/DoctrineORMModule

## Tips

* Use php >= 7
