# todo

A simple todo web application.

## Install

First, you need to fetch some dependencies:

### Fat-Free Framework

Download the Fat-Free Framework from [http://fatfreeframework.com/](http://fatfreeframework.com/) and extract the content of the `lib` folder into the `lib` folder of this application.

### DejaVu Sans ExtraLight

Get this light front from [http://www.fonts2u.com/download/dejavu-sans-extralight.font-face](http://www.fonts2u.com/download/dejavu-sans-extralight.font-face) and extract the files into the `ui/DejaVu_Sans_ExtraLight` directory

Now that all dependencies are fetched, you can copy the application to your webserver. Just one thing: Change the permissions of the `tmp` directory so the webserver can write to it.

## Configuration

Todo is configured with the `config.ini` file.

* `DEBUG` – Unless you intend to further develop this application, leave this to `0`
* `sql_dsn` – The [PDO DSN]() for connecting to the database
* `sql_user` – Username for the database access
* `sql_pass` – Password for the database access
* `mail_from` – Outgoing mails (confirmation codes, password reset mails) will use this E-Mail address for the `From` field
* `appname` – The name of the application

### Protecting the configuration file

It is usually a bad idea that the config file is accessible via the web, since people can then see the username and password of your database.

One possibility to prevent this, is to move the file into a directory that can not be accessed via the web and then modifying `index.php`: Search for the line `$f3->config('config.ini');` and change the `config.ini` part to point to the new location.
