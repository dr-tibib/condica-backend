# Laravel Sail

Note: This guide covers Sail integration using MySQL.
The default Sail user's name is determined by the DB_USERNAME variable in your .env.

The default Sail user can only perform the create, read, update and delete operations in the central database. To allow the user to perform these operations in the tenant databases too, you need to grant the Sail user access to all databases.

```sql
GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;
```

You have to grant the privileges every time you re-run a container. To automate granting the privileges, create an SQL file with the previously mentioned SQL statements to grant all privileges to the Sail user. Then, add the path to the SQL file to docker-compose.yml's MySQL volumes.
