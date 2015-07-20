MySqli Extended
===============

A better experience of PHP MySqli.

Shortcuts
---------

**BenTools\MySqliExtended\MySqliExtended** is a child class of *\MySqli*. It has several shortcut methods to fetch data :

- *sqlArray()* -> fetch a multi-dimensionnal array, basically several rows in a table.
- *sqlRow()* -> fetch an associative array, for example a row.
- *sqlColumn()* -> fetch the 1st column as an indexed array.
- *sqlValue()* -> fetch a specific value.

```php
$cnx = new MySqliExtended();
$cnx->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

$cnx->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$query = "SELECT 'Bill' AS firstname, 'Gates' AS lastname UNION SELECT 'Tim' AS firstname, 'Cook' AS lastname";
$result = $cnx->sqlArray($query);

Output:
array (
  0 => 
  array (
    'firstname' => 'Bill',
    'lastname' => 'Gates',
  ),
  1 => 
  array (
    'firstname' => 'Tim',
    'lastname' => 'Cook',
  ),
)


$result = $cnx->sqlRow($query);
Output:
array (
  'firstname' => 'Bill',
  'lastname' => 'Gates',
)


$result = $cnx->sqlColumn($query);
Output:
array (
  0 => 'Bill',
  1 => 'Tim',
)


$result = $cnx->sqlValue($query);
Output:
'Bill'
```

Prepared Statements
-------------------

Working with prepared statements is now easier than before :
```php
// Before :
$query = "INSERT INTO `ceo` (firstname, lastname, years_in_company) VALUES (?, ?, ?)";
$stmt = $cnx->prepare($query);
$firstname = 'Larry';
$lastname = 'Page';
$years_in_company = 12;
$stmt->bind_param('ssi', $firstname, $lastname, $years_in_company);
$stmt->execute();

// Now :
$stmt = $cnx->prepare($query);
$stmt->sql(array(
    'Larry',
    'Page',
    12
));

// or directly :
$cnx->sql($query, array(
    'Larry',
    'Page',
    12
));

// or very shortly :
$cnx($query, array(
    'Larry',
    'Page',
    12
));
```
You no longer need to create variables, create references and create a weird type-hinting string, i.e. 'ssi'.

You can also use **named parameters** (like with **PDO**), that you can duplicate in the same query :
```php
$query = "INSERT INTO `ceo` (firstname, lastname, years_in_company) VALUES (:firstname, :lastname, :years_in_company) ON DUPLICATE KEY UPDATE years_in_company = :years_in_company";
$cnx->sql($query, array(
    'firstname' => 'Larry',
    'lastname' => 'Page',
    'years_in_company' => 12
));
```

Easily work with **prepared statements**.

```php
$query = "SELECT firstname, lastname FROM `ceo` WHERE firstname LIKE ?";
$result = $cnx($query)->sqlRow('bill%');

Ouput :
array (
  'firstname' => 'Bill',
  'lastname' => 'Gates',
)

$result = $cnx($query)->sqlRow('larry%');

Ouput :
array (
  'firstname' => 'Larry',
  'lastname' => 'Page',
)

$query = "SELECT firstname, lastname FROM `ceo` WHERE (firstname LIKE :firstname OR years_in_company > :years)";
$result = $cnx($query)->sqlArray(array(
    'firstname' => 'larry%',
    'years'      => 10
));

Output:
array (
  0 => 
  array (
    'firstname' => 'Bill',
    'lastname' => 'Gates',
  ),
  1 => 
  array (
    'firstname' => 'Tim',
    'lastname' => 'Cook',
  ),
  2 => 
  array (
    'firstname' => 'Larry',
    'lastname' => 'Page',
  ),
)

```

Installation
------------
Add the following line into your composer.json :

    {
        "require": {
            "bentools/mysqliextended": "1.0.x"
        }
    }  
    
Enjoy.