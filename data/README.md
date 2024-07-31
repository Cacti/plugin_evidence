# how to import prepared enterprise-numbers

Import may take some time, so it is not part of the installation.
First poller run tries import file enterprise_numbers.sql automatically.
You can import it via mysql client:
mysql -u cacti_user -p cacti_db < ent.sql


# how to prepare and import actual enterprise-numbers

1) download from
http://www.iana.org/assignments/enterprise-numbers/enterprise-numbers

2) run prepare_sql.php

3) import file enterprise_numbers.sql via mysql:
mysql -u cacti_user -p cacti_db -e 'delete from plugin_evidence_organization'
mysql -u cacti_user -p cacti_db < ent.sql

