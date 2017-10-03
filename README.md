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

Some Client's DB can have issue of integrity preventing the completness of the migration. The following requests correct the bug. It must be done before the migration on the client's DB

Request 1 for recommandation

	update dims_mod_smile_anr_recommandations_risks

	set dims_mod_smile_anr_recommandations_risks.a_id = (SELECT dims_mod_smile_anr_qualif.id_asset
	from dims_mod_smile_anr_qualif 
	where dims_mod_smile_anr_recommandations_risks.risk_id = dims_mod_smile_anr_qualif.id),

	dims_mod_smile_anr_recommandations_risks.v_id = (SELECT dims_mod_smile_anr_qualif.id_vul
	from dims_mod_smile_anr_qualif
	where dims_mod_smile_anr_recommandations_risks.risk_id = dims_mod_smile_anr_qualif.id),

	dims_mod_smile_anr_recommandations_risks.m_id = (SELECT dims_mod_smile_anr_qualif.id_menace
	from dims_mod_smile_anr_qualif 
	where dims_mod_smile_anr_recommandations_risks.risk_id = dims_mod_smile_anr_qualif.id)

	where dims_mod_smile_anr_recommandations_risks.a_id = 0 and dims_mod_smile_anr_recommandations_risks.v_id = 0 and 		dims_mod_smile_anr_recommandations_risks.m_id = 0 
	and dims_mod_smile_anr_recommandations_risks.biblio_global_id = 0 ; 

Request 2 for the consequences 
	
	insert into dims_mod_smile_assoc_consequences (instance_id, biblio_id, anr_id, type_id)
	select dims_mod_smile_anr_instance.id, dims_mod_smile_anr_instance.biblio_id, dims_mod_smile_anr_instance.anr_id, 			dims_mod_smile_biblio_scales_impact_types.id
	from dims_mod_smile_anr_instance, dims_mod_smile_biblio_scales_impact_types
	where dims_mod_smile_anr_instance.id not in (select dims_mod_smile_assoc_consequences.instance_id from dims_mod_smile_assoc_consequences)
	and dims_mod_smile_biblio_scales_impact_types.anr_id = dims_mod_smile_anr_instance.anr_id
	

Writing error in Doctrine cache :
	
	chmod -R 777 ./data/DoctrineORMModule

## Tips

* Use php >= 7
* By default the question of the evaluation of trends are migrated as questions added by user. If you want to make as "system question" set the mode at 0 in the new client table "questions" for each line. 
