
Project info:
* status: **active**
* code style: **current**
* architecture style: **current**
* year: **2022**
* version: beta

# Confdotd
Library for working with conf.d directories  

Selecting config files:
* File - files only
* Regexp - filename regular expression
* ShellPattern - filename shell pattern

Examples:

```PHP
...autoload

use confdotd\conditions\File;
use confdotd\Confdotd;

$confd = new Confdotd( '/etc/nginx/sites-available', new File, '/etc/nginx/sites-enabled' );
$sites = $confd->List( );

foreach( $sites as $site ) {
    echo $site->name.' '.( $site->enabled ? 'yes' : 'no' )."\n";
}
```
