# Lisk Pool Php Symfony Bundle
This package provides a Symfony 3.3 bundle to run your own Lisk pool. It is fairly easy to install and configure.

## Prerequisites
### Install packages
Besides all Symfony dependencies, install: 
- The following packages: memcached, mariadb-server, php, php-curl, php-mysqlnd, php-bcmath, php-pecl-memcached, composer.
- A webserver (eg. nginx or apache)
- A Lisk node.

For more information, see:  
<a href="https://docs.lisk.io/docs" target="_blank">Lisk Node</a><br>
<a href="https://mariadb.org" target="_blank">MariaDB (MySQL)</a><br>
<a href="https://memcached.org" target="_blank">Memcached</a><br>
<a href="https://nginx.org" target="_blank">Nginx</a> or <a href="https://httpd.apache.org/" target="_blank">Apache</a><br>

The MariaDB/MySQL server is used to store voter forging shares, payout history and forged block information. You will need to create an **empty** database and assign a valid MySQL user + password to this database.

The Memcached server is used to store information about the most up to date Lisk nodes, if you are running multiple nodes. Other parts of the projects use this information to determine the Lisk node to forge on and communicate with.  

If you need to have a PHP integration for the Lisk API in any other projects, check out <a href="https://github.com/goforlisk/" target="_blank">Symfony Lisk-PHP integration</a>. This bundle is used in this project to communicate with the Lisk node using PHP.

## Installation instructions
### Step 1: Clone the Github repository
```sh
git clone https://github.com/goforlisk/liskpool.git
```

### Step 2: Modify the config.yml file
Make sure the YAML file is complete before proceding to the next step, otherwise you will likely run into configuration errors.

```yaml
lisk_php:
    base_url: "https://main-01.goforli.sk" # Enter the Lisk Node URL here
    network_hash: "ed14889723f24ecc54871d058d98ce91ff2f973192075c0155ba2b7b70ad2511" # This is the network hash of the mainnet
    #network_hash: "da3ed6a45429278bac2666961289ca17ad86595d33b31037615d4b8e8f158bba" # This is the network hash of the testnet, uncomment this line and comment the mainnet line if you wish to use the testnet

lisk_pool:
    delegate_username: "goforlisk" # Enter your delegate username
    memcached:
      host: 'localhost' # Memcached (default) host
      port: 11211 # Memcached (default) port
    forging:
      nodes:
        - 'https://main-01.goforli.sk' # Enter your lisk nodes here, specify at least one node
        - 'https://main-02.goforli.sk' # Adding a second node is optional, a daemon command will determine which node is best synced and use that one for forging/communication
      secret: '' # Enter your secret here
      second_secret: '' # Enter your second secret here, this field is only required if you are using a second secret
      public_key: '4b9438abb739aa6fb17be78cee8e61ad86285ad661c5b7b8527f939fbea3d7ea' # Enter the public key of your delegate username here
      fee_in_percentage: 10 # This is the fee that is kept by the pool in percent of the total rewards (forging rewards + transaction fee rewards), eg 10 means that you will keep 10% and share 90% with your voters proportionally to their voting weight
      minimum_payout: 1 # The minimum balance required to pay a voter INCLUDING fee, so a payout of 1 means the user will receive a payment when the forging balance gets to 1 LSK, the user will then receive 0.9 LSK (1LSK - 0.1LSK fee)
```

### Step 3: Install composer dependencies
From the root of your project, run:
```sh
composer install
```

After installation, composer will ask you values for several parameters. When asked for database credentials, use the database name and credentials that you have created in the prerequisites section.

### Step 4: Install the database schema
Doctrine will manage the database schema for you, simply run:
```sh
php bin/console doctrine:schema:update --force
```

### Step 5: Run the daemons
There are 3 daemons available which should ideally be installed as services. For the example below the package `screen` is being used. If you don't have this package and you will not install the daemons as services, install the package `screen` first.

**NOTE:** The "best synced node" daemon will only flag a node as "best" if the consensus is "100%". As long as your host is syncing, it will not qualify as "best node". Make sure the node gets fully synced, until that time the daemon will report that no node qualifies as best node.  

To start the "best synced node" daemon, run from the project root:
```sh
screen -dmS bestnode php bin/console lisk:daemon:bestnode
```

To start the "block processing" daemon, run from the project root:
```sh
screen -dmS blocks php bin/console lisk:daemon:processblocks
```

To start the "best synced node" daemon, run from the project root:
```sh
screen -dmS payments php bin/console lisk:daemon:processpayments
```

This will run the daemons in a backgrounded screen session that will survive disconnecting the SSH sesion. 

To view what the daemons are doing, run the following command:
```sh
screen -x NAME-OF-SCREEN
```
Where `NAME-OF-SCREEN` can be `bestnode`, `blocks` or `payments`

The `bestnode` and `blocks` daemon runs an iteration every 10 seconds. The `payments` daemon will run hourly.

To return from the detached screen session back to your SSH session, type `ctrl`+`a`+`d`

### Step 6: Configure a webserver
Configuring a webserver can be done in many ways, the example below is a sample configration for NginX only containing the server block, but it does not really matter how this is done exactly. Just use the way that is most convenient for your situation.   
**It is highly recommended to run the pool over an HTTPS enabled connection, the configuration below is just for reference**

```
server {
    listen       80 http2;
    listen       [::]:80 http2;
    server_name  pool.goforli.sk;
    root         /home/liskpool/liskpool/web;

    location / {
            # try to serve file directly, fallback to app.php
            try_files $uri /app.php$is_args$args;
    }

    # DEV
    # This rule should only be placed on your development environment
    # In production, don't include this and don't deploy app_dev.php or config.php
    location ~ ^/(app_dev|config)\.php(/|$) {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    # PROD
    location ~ ^/app\.php(/|$) {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            fastcgi_param DOCUMENT_ROOT $realpath_root;
            internal;
    }

    # return 404 for all other php files not matching the front controller
    # this prevents access to other php files you don't want to be accessible.
    location ~ \.php$ {
            return 404;
    }

    error_log /var/log/nginx/lisk-php_error.log;
    access_log /var/log/nginx/lisk-php_access.log;
}
```

After restarting your webserver the pool web page should now be accessible on the specified hostname.

## Need help?
If you need help, send me a message on Reddit (wtfbbq89), lisk.chat (goforlisk) or send an e-mail to support@goforli.sk

## Want to contribute?
Contributions and/or suggestions are very welcome, together we can make the software better and make Lisk strong. File a PR or contact me in one of the ways mentioned above.

## Like our software?
If you like our software, please consider voting for **goforlisk** as a delegate. Once we can forge, the pool shares **85%** right back to our voters proportionally. Another **5%** is reserved to be able to support other people with great ideas for the Lisk community. The final 10% is kept to keep our nodes running and be able to keep spending time on making tools for Lisk. **Thanks a lot if you decide to vote for us!**