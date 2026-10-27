<?php
header("Content-Type: application/json");

define("DB_HOST", "localhost");
define("DB_USERNAME", "u153404388_db_DuyPham");
define("DB_PASSWORD", "Database2026!");
define("DB_NAME", "u153404388_DuyPham");

$mysqli = new mysqli(
    DB_HOST,
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

if ($mysqli->connect_error) {
    echo json_encode([
        "error" => "Database connection failed"
    ]);
    exit;
}

/* Choose the node from the URL */
$node_name = $_GET["node"] ?? "node_1";

/* Allow only names such as node_1, node_2, node_3 */
if (!preg_match("/^node_[0-9]+$/", $node_name)) {
    echo json_encode([
        "error" => "Invalid node name"
    ]);
    exit;
}

$sql = "
    SELECT node_name, time_received, temperature
    FROM sensor_data
    WHERE node_name = ?
    ORDER BY time_received ASC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $node_name);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$mysqli->close();
?>
