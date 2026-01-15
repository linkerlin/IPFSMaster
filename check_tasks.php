<?php
require 'src/Models/Database.php';

$db = Database::getInstance();

$result = $db->query('SELECT * FROM background_tasks ORDER BY id DESC LIMIT 10');

echo "=== Background Tasks ===\n\n";

$hasRows = false;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $hasRows = true;
    print_r($row);
    echo "\n";
}

if (!$hasRows) {
    echo "No tasks found in database.\n";
}
