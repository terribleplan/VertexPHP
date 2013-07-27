#VertexPHP
This is a pure-PHP implementation of a driver to the VertexDB graph database.

Currently the driver should be considered alpha-quality. If you find any bugs feel free to add a report, or issue a pull request.
##Usage
Using VertexPHP is as simple as
```php
require_once('vertex.php');
//Note that there is no trailing slash
$database = new VertexDB("http://127.0.0.1:8001/");

//do stuff
```
##TODO
* Add documentation, specifically for what is returned by each method.

* Test, there have been some tests, but no extensive ones, and not of every feature.

* Determine what to do about the transaction system.
###License
VertexPHP is available under a 3-clause BSD license, which you can find at the top of vertex.php