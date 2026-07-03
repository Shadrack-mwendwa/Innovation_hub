<?php
require_once 'config/database.php';

echo "<h1>Trainings in Database</h1>";

$trainings = $pdo->query("SELECT * FROM trainings ORDER BY start_date DESC")->fetchAll();

if(empty($trainings)) {
    echo "<p style='color:red'>No trainings found in database!</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Start Date</th><th>Deleted At</th><th>Visible to Users</th></tr>";
    foreach($trainings as $t) {
        $visible = ($t['status'] == 'upcoming' && strtotime($t['start_date']) > time() && is_null($t['deleted_at'])) ? 'Yes' : 'No';
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td>{$t['title']}</td>";
        echo "<td>{$t['status']}</td>";
        echo "<td>{$t['start_date']}</td>";
        echo "<td>" . ($t['deleted_at'] ?? 'NULL') . "</td>";
        echo "<td style='color:" . ($visible == 'Yes' ? 'green' : 'red') . "'>$visible</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Query for public display:</h2>";
echo "<pre>SELECT * FROM trainings WHERE status = 'upcoming' AND start_date > NOW() AND deleted_at IS NULL ORDER BY start_date ASC LIMIT 3</pre>";

$public = $pdo->query("SELECT * FROM trainings WHERE status = 'upcoming' AND start_date > NOW() AND deleted_at IS NULL ORDER BY start_date ASC LIMIT 3")->fetchAll();
echo "<p>Found: " . count($public) . " trainings for public display</p>";
?>