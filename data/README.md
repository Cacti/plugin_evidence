# Enterprise-number data

Evidence includes `enterprise-numbers.sql`. The poller imports it when the
organization table needs initialization; the import can take several minutes.
It is not part of the initial plugin installation.

## Import the bundled file

Back up the Cacti database first. The bundled SQL starts by deleting the
contents of `plugin_evidence_organization`, then replaces them with its vendor
reference data. This operation does not remove device inventory history.

From the plugin's `data` directory, run:

```console
mysql -u CACTI_DB_USER -p CACTI_DB_NAME < enterprise-numbers.sql
```

Replace the account and database placeholders with the Cacti database values.
The client prompts for the password; do not put it on the command line.

## Prepare a replacement file

The legacy `prepare_sql.php` helper reads a local file named
`enterprise-numbers` and writes `ent.sql`. These are different filenames from
the bundled `enterprise-numbers.sql`.

1. Download the [IANA Private Enterprise Numbers registry][iana] as
   `enterprise-numbers` in this directory.
2. Run `php prepare_sql.php`.
3. Inspect `ent.sql` and compare its organization names and numeric IDs with
   the input before importing. The helper expects the registry's text layout;
   do not replace working reference data if parsing produced incomplete rows.
4. Back up the existing organization table. To replace it, clear only
   `plugin_evidence_organization` and import the reviewed `ent.sql` using the
   database client. Perform the replacement during a maintenance window.

The generated `ent.sql` contains inserts; unlike the bundled file, it does not
clear the existing table itself. Do not run it over existing primary keys and
assume that every organization was updated.

[iana]: https://www.iana.org/assignments/enterprise-numbers/enterprise-numbers
