<?php 
$db = new SQLITE3('cars.sqlite');

if (($handle = fopen("cars_data_NA.csv", "r")) !== FALSE) {
    // Skip the header row if your CSV file has one
    // fgetcsv($handle, 1000, ","); // Uncomment this line if your CSV has a header row
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        $Make = SQLite3::escapeString($data[0]);
        $Model = SQLite3::escapeString($data[1]);
        $Image = SQLite3::escapeString($data[2]);

        $SQLinsert = "INSERT INTO Cars (Make, Model, Image)";
        $SQLinsert .= " VALUES ('$Make', '$Model', '$Image')";

        $db->exec($SQLinsert);
    }
    fclose($handle);
}
?>