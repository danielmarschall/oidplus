
# Setup a PostgreSQL database for OIDplus

As root user: `su - postgres`.

Then enter `psql`.

Then enter these SQL commands:

                CREATE ROLE oidplus WITH LOGIN PASSWORD 'your_password';

                CREATE DATABASE oidplus WITH
                  OWNER = oidplus
                  TEMPLATE = template0
                  ENCODING 'UTF8'
                  TABLESPACE = pg_default
                  LC_COLLATE = 'C'
                  LC_CTYPE = 'C'
                  CONNECTION LIMIT = -1;

                GRANT ALL PRIVILEGES ON DATABASE oidplus TO oidplus;

Then run the command generated by setup/ , for example:

    curl -s "https://.../setup/struct_with_examples.sql.php?prefix=dev_&database=oidplus&slang=pgsql" | psql -U oidplus -d oidplus -a

Change the base config file as described by setup/

