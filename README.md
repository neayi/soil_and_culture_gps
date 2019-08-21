# soil_and_culture_gps
Finds the soil and possible cultures for a given GPS location in France

The script finds the GPS location using a postal code or the IP address, then finds the best matches in two tables:
* RPG (Registre Parcellaire Graphique) which contains all the cultures declared to the PAC: https://www.data.gouv.fr/fr/datasets/registre-parcellaire-graphique-rpg-contours-des-parcelles-et-ilots-culturaux-et-leur-groupe-de-cultures-majoritaire/ (visualisation here: https://www.geoportail.gouv.fr/donnees/registre-parcellaire-graphique-rpg-2017)
* The Soil database: https://doi.org/10.15454/BPN57S (visualisation here: https://plateforme.api-agro.fr/explore/dataset/base-de-donnees-geographique-des-sols-de-france-a-11-000-000-version-3280/information/)



Todo:
* Create 3 scripts:
** config.php will hold the database identifiers, to be used by the two other scripts, as well as the URLs for the external databases
** install.php will create (if necessary) a temp folder, then copy in this folder the external databases. It will then create the database and perform the few DB changes required (add the proper indexes, change the types of some of the columns, etc.), then load the external databases with ogr2ogr
** query.php will return some json with soil and culture information, to be used with either a postal code, IP address or GPS coordinates.

* Add some wiki pages to know how to install ogr2ogr, what to do with the different GPS coordinates types, and so on
** How to install the DB, and configure config.php
** How to translate GPS coordinates and references about it (wikipedia and so on)
** What web services we use (for IP addresses location + postal codes)
** ...

