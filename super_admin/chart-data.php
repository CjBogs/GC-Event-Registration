<?php
require_once '../config.php';

$sql = "
  SELECT e.title, COUNT(r.id) AS participants
  FROM events e
  LEFT JOIN event_registrations r ON e.id = r.event_id
  GROUP BY e.title
  ORDER BY participants DESC
";

$result = $conn->query($sql);

$data = [];

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'event' => $row['title'],
      'participants' => (int)$row['participants']
    ];
  }
}

header('Content-Type: application/json');
echo json_encode($data);
