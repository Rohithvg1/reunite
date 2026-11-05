# Reunite

Reunite is a lost and found board for a college community. Members post lost or found items, search by category or location, answer a private identifying question, and confirm a return. Administrators can hide and restore posts. Six labelled sample listings show how the board works.

The application uses PHP 8.2+, Apache, MariaDB, HTML, CSS and JavaScript. It needs the PHP extensions `pdo_mysql`, `mbstring` and `fileinfo`.

## Run with XAMPP

1. Place the project in `C:\xampp\htdocs\reunite`.
2. Create `data/config.private.php` with the contents below to enable local setup.
3. Start Apache and MySQL. Open `http://localhost/reunite/`, or `http://localhost:8080/reunite/` if Apache uses port 8080.

```php
<?php
return ['ENV' => 'local'];
```

With a standard local XAMPP database, the first visit creates the tables and sample items. For a fresh local database, the generated administrator sign-in is in `data/local-admin.txt`. If your local MySQL installation uses a password or a different port, add `DB_PASSWORD` or `DB_PORT` to the same settings file.

## Host the website

1. Use an HTTPS host that runs PHP and keeps uploaded files between restarts. Create a MariaDB database and grant a dedicated user permission to manage tables in that database.
2. Deploy a source archive into the Apache website directory, including the three `.htaccess` files. Allow the PHP process to write to `data/` and `uploads/`. Use persistent storage for both the database and uploads.
3. Set the following environment variables in the hosting control panel. A shared host can instead store the same values in the private PHP file, using keys without the `REUNITE_` prefix. Use the normal hosted configuration for this installation.

| Environment variable | Value |
| --- | --- |
| `REUNITE_DB_HOST` | Hostname supplied by the database provider |
| `REUNITE_DB_PORT` | MariaDB port, usually 3306 |
| `REUNITE_DB_NAME` | Provisioned database name |
| `REUNITE_DB_USER` | Dedicated database username |
| `REUNITE_DB_PASSWORD` | Database password |
| `REUNITE_ADMIN_EMAIL` | Email address for the initial administrator |
| `REUNITE_ADMIN_PASSWORD` | Separate administrator password, 12–72 bytes, for the first setup |

4. Open the site's HTTPS address. The first request creates the schema and six sample items. The administrator password is hashed in the database. Once initialization succeeds, you can remove the initial administrator password from the hosting settings.
5. Verify that another browser can browse, register, create a report with a photo, send a claim and confirm a return. Protect `data/` from public access and include the database and `uploads/` folder in backups.

On InfinityFree, create a database under **MySQL Databases**, use its assigned hostname, and upload the application files to the domain's `htdocs` directory. Keep the database and administrator settings in `data/config.private.php` on the host. See the provider's [database setup](https://forum.infinityfree.com/t/how-to-setup-a-new-mysql-database/49342) and [file upload guide](https://forum.infinityfree.com/t/how-to-migrate-your-website-to-infinityfree/49315).
