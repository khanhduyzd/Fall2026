<?php
$host = "srv630.hstgr.io";
$user = "u153404388_db_DuyPham";
$pass = "9D@fGFo9";
$db   = "u153404388_DuyPham";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$sql = "SELECT received_time, value
        FROM pot_sensor_value
        ORDER BY received_time ASC, id ASC";

$result = $conn->query($sql);

$times = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $times[] = $row["received_time"];
    $values[] = (float)$row["value"];
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Potentiometer Data</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h1>ESP8266 Potentiometer Readings</h1>

<canvas id="chart"></canvas>

<script>
const times = <?php echo json_encode($times); ?>;
const values = <?php echo json_encode($values); ?>;

new Chart(document.getElementById("chart"), {
    type: "line",
    data: {
        labels: times,
        datasets: [{
            label: "Potentiometer Value",
            data: values,
            borderColor: "blue",
            borderWidth: 2,
            fill: false
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                suggestedMax: 1023
            }
        }
    }
});
</script>

</body>
</html>
