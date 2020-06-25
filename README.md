# LoadBalancer example application

## LoadBalancer
This is an example LoadBalancer application which can handle requests using two different kinds of balancing methods.
The web requests will be forwarded to the host matching the algorithm.

###Supported algorithm

####roundrobin
The load balancer will simply pass the requests sequentially in rotation to each of the configured and available hosts.

####load
The load balancer will either take the first host that has a load under a specific value (default 0.75) or if all hosts in the list are above the limit, it will take the one with the lowest load.

## Installation

#### Prerequisites

These instructions assume you have already installed:

- PHP _(7.4 or higher)_
- Web Server _(Recommeneded: Apache / Nginx. Use of PHP's built-in development server is also possible)_
- [Composer](https://getcomposer.org)
- A Git client


#### Install LoadBalancer

Git clone the application:

``` bash
git clone https://github.com/wawrzynkiewicz/lodbalancer.git
```

Install required symfony framework and it's components

``` bash
cd loadbalancer
composer install
```

**Note:** If composer is installed locally instead of globally, the first command will start with `php composer.phar`.

Configure your preferred webserver to run web application, here is an example of a virtual host for Apache:
```
<VirtualHost *:80>
    ServerAdmin <YOUR E-MAIL>
    ServerName <HOSTNAME>

    CustomLog <PATH OF APPLICATION>/var/log/apache_access.log combined
    ErrorLog <PATH OF APPLICATION>/var/log/apache_error.log

    DocumentRoot <PATH OF APPLICATION>/public

    <Directory <PATH OF APPLICATION>>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
        Require all granted
    </Directory>

</VirtualHost>
```

###Test application:

Call the index page of your configured webserver

```
http://<HOSTNAME>/
```

and look into the *apache_error.log* log file to see info and debug messages on what's going on.

###Configure application

Take a deeper look into the *config/services.yaml* to adapt the configuration regarding load balancing algorithm or available hosts and their load.


