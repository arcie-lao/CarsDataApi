<?php
# https://www.leaseweb.com/labs/2015/10/creating-a-simple-rest-api-in-php/

# URL components should look like this: http://localhost/api.php/{$table}/{$id}

# it is assumed that the first column in the table is the primary key

# USAGE: 
#   http://localhost:8888/api.php/Cars
#   http://localhost:8888/api.php/Cars/3

# Change this to 1 to debug
define("DEBUG", 0);

# get HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
if (!isset($_SERVER['PATH_INFO'])) {
    echo "Proper usage is http://localhost:8888/api.php/Cars/";
    exit;
  }
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$input = json_decode(file_get_contents('php://input'), true);

# Create a database
$db = new SQLITE3('cars.sqlite');

# Create a table
$SQL_create_table = "CREATE TABLE IF NOT EXISTS Cars (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    Make VARCHAR(80),
    Model VARCHAR(80),
    Image VARCHAR(50)
  );";
$db->exec($SQL_create_table);

if (DEBUG === 1) {
    echo "<h3>request</h3>";
    var_dump($request);
}

# Inserting Data

# Retrieving table from the path
if (isset($request[0])) {
    $table = $request[0];
  
    if (DEBUG === 1) {
      echo "<h3>table</h3>";
      var_dump($table);
    }
  } else {
    $table = NULL;
}

# Retrieving id from the path
if (isset($request[1])) {
    $id = $request[1];

    if (DEBUG === 1) {
        echo "<h3>id</h3>";
        var_dump($id);
    }
} else {
    $key = NULL;
}

# If user is trying to insert/update we need to get the data from the body
if (isset($input)) {
    // escape the columns and values from the input object
    $columns = preg_replace('/[^a-z0-9_]+/i', '', array_keys($input));
    $values = array_map(function ($value) {
      if ($value === null) return null;
      return SQLite3::escapeString((string)$value);
    }, array_values($input));
  
    // build the SET part of the SQL command
    $insertSet = '';
    $insertVal = '';
    $updateSet = '';
    for ($i = 0; $i < count($columns); $i++) {
      $insertSet .= ($i > 0 ? ',' : '') . '`' . $columns[$i] . '`';
      $insertVal .= ($values[$i] === null ? 'NULL' : '"' . $values[$i] . '",');
  
      $updateSet .= ($i > 0 ? ',' : '') . '`' . $columns[$i] . '`=';
      $updateSet .= ($values[$i] === null ? 'NULL' : '"' . $values[$i] . '"');
    }
  
    $insertVal = str_replace("\"", "'", $insertVal);
    $insertVal = substr_replace($insertVal, "", -1);
  
    if (DEBUG === 1) {
      echo "<h3>set</h3>";
      echo "*" . $insertSet . "*";
      echo "<h3>val</h3>";
      echo "*" . $insertVal . "*";
    }
}

# Get the pk in table
$result = $db->query("SELECT * FROM `$table`");
$pk = $result->columnName(0);

# Build the SQL command based on HTTP method
switch ($method) {
    case 'GET':
      $sql = "SELECT * FROM `$table`" . ($key ? " WHERE $pk=$key" : '');
      break;
    case 'PUT':
      $sql = "UPDATE `$table` SET $updateSet WHERE $pk=$key";
      break;
    case 'POST':
      $sql = "INSERT INTO `$table` ($insertSet) VALUES ($insertVal)";
      break;
    case 'DELETE':
      $sql = "DELETE FROM `$table` WHERE $pk=$key";
      break;
}

if (DEBUG === 1) {
    echo "<h3>SQL</h3>";
    echo $sql;
}

# Execute the SQL command
$result = $db->query($sql);
if (!$result) {
    http_response_code(404);
    die("Error in fetch " . $db->lastErrorMsg());
  }
  
if (DEBUG === 1) {
    echo "<h3>JSON</h3>";
}

# If the method is GET we need to return the data
if ($method == 'GET') {
    header('Content-type:application/json;charset=utf-8');
    $collection = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $collection[] = $row;
    }
    if (count($collection) > 1)
      echo json_encode($collection);
    else {
      if (isset($collection[0]))
        echo json_encode($collection[0]);
      else
        http_response_code(404);
    }
  } elseif ($method == 'POST') {
    echo $db->lastInsertRowid();
  } else {
    echo $db->changes();
}

$db->close();

?>