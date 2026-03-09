<?php
require_once 'db_con.php';

header('Content-Type: application/json; charset=utf-8');

$search = trim($_GET['search'] ?? '');
$response = [
    'query' => $search,
    'results' => [],
];

if ($search === '') {
    echo json_encode($response);
    exit;
}

$searchTerm = "%{$search}%";
$sql = "
    SELECT a.id,
           CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as title,
           c.name as college,
           p.name as program,
           b.year as batch_year
    FROM alumni a
    JOIN colleges c ON a.college_id = c.id
    JOIN programs p ON a.program_id = p.id
    JOIN batches b ON a.batch_id = b.id
    WHERE a.is_active = 1
      AND (
          a.first_name LIKE ?
          OR a.middle_name LIKE ?
          OR a.last_name LIKE ?
          OR a.student_id LIKE ?
          OR a.email LIKE ?
      )
    ORDER BY a.created_at DESC
    LIMIT 6
";

$params = array_fill(0, 5, $searchTerm);
$stmt = query($sql, $params);
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $parts = array_filter([
        $row['college'] ?? null,
        $row['program'] ?? null,
        isset($row['batch_year']) ? 'Batch ' . $row['batch_year'] : null,
    ]);

    $description = implode(' • ', $parts);
    if ($description === '') {
        $description = 'Alumni profile';
    }

    $response['results'][] = [
        'title' => trim($row['title']),
        'description' => $description,
        'link' => 'alumni_list.php?search=' . urlencode($search),
        'type' => 'alumni',
    ];
}

echo json_encode($response);
