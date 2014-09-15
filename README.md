


> Written with [StackEdit](https://stackedit.io/).

Selfregister
=========

This is a SimpleSAMLphp module that allows registration of users accounts. The original version was developed by [UNINETT](https://rnd.feide.no/2010/03/25/new_simplesamlphp_module_selfregister) and supported LDAP as a backend.
This fork adds support for SQL databases as the back-end.

The module needs an `sqlauth:SQL`   authentication source as the place to store user accounts. You can use an existing authsource, just make sure the credentials used allow for writing.

People that want to sign up for an account need to fill in their e-mail address, and they get sent a URL with a token to confirm the address.
Upon verification the user can then needs choose a username, a password, and values for first and last name.
These values are stored in the SQL back-end.
To store the password securely it is hashed with a salt, which is saved in a separate database column. This approach allows the database to do the password verification.

Enable this module the standard way (i.e. touching the file `enable`  in the module directory, and copy the default configuration file to `config/`).

MySQL back-end
================


The default configuration file `module_selfregister.php` contains all the necessary statements. 


###Database set-up

Create the database,  add a user, and assign permissions:

    CREATE DATABASE ssp_selfregister;
    GRANT ALL on ssp_selfregister.* to 'ssp_user'@'localhost' IDENTIFIED by 'hackme';
    FLUSH PRIVILEGES;

Create the table that will hold you users:
    
    CREATE TABLE users (
        `userid` varchar(32) NOT NULL,
        `password` text NOT NULL,
        `salt` blob,
        `firstname` text,
        `lastname` text,
        `created` datetime NOT NULL,
        `email` varchar(255) NOT NULL,
        `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`userid`),
        UNIQUE KEY `UE` (`email`)
        )


### authsource set-up
---------------------------
Create the accompanying authsource in `config/authsources.php`:

    'selfregister-mysql' => array(
        'sqlauth:SQL',
            'dsn' => 'mysql:host=localhost;dbname=ssp_selfregister',
            'username' => 'ssp_user',
            'password' => 'hackme',
            'query' => 'SELECT * FROM users WHERE userid = :username
                        AND password = SHA2 (
                            CONCAT(
                                (SELECT salt FROM users WHERE userid = :username),
                                :password
                            ),
                            512
                        )',
        ),



PostgreSQL back-end
================

###Database set-up

As the postgres super user, create a new role, and a new database that is owner by the new user:

    createuser -D -I -R -S -P ssp_user
    createdb -O ssp_user -T template0 ssp_selfregister

In order to use the crypto that is needed to do the password verification, you need to add the pgcrypto extension to the database. As the postgres super user:

    psql ssp_selfregister
    CREATE EXTENSION pgcrypto;

This in turn might depend on an extra package, for Debian/Ubuntu this is the `postgresql-contrib` package.

####authsource set-up

Create the accompanying authsource in `config/authsources.php` (and remember to update the `auth` statement in `module_selfregister.php`_:

    'selfregister-pgsql' => array(
            'sqlauth:SQL',
            'dsn' => 'pgsql:host=ip6-localhost;dbname=ssp_selfregister',
            'username' => 'ssp_user',
            'password' => 'hackme',
            'query' => "
                    SELECT * FROM users WHERE userid = :username
                    AND password = encode(
                        digest (CONCAT((SELECT salt FROM users WHERE userid = :username), password::TEXT), 'sha512'),
                        'hex')",
    ),

