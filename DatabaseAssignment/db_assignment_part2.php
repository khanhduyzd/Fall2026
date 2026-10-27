<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$servername = "localhost";
$username = "u153404388_db_DuyPham";
$password = "Database2026!";
$dbname = "u153404388_DuyPham";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$register_sql = "
    SELECT node_name, manufacturer, longitude, latitude
    FROM sensor_register
    ORDER BY node_name ASC
";

$register_result = $conn->query($register_sql);

if (!$register_result) {
    die("Error reading sensor_register: " . $conn->error);
}

$data_sql = "
    SELECT node_name, time_received, temperature, humidity
    FROM sensor_data
    ORDER BY node_name ASC, time_received ASC
";

$data_result = $conn->query($data_sql);

if (!$data_result) {
    die("Error reading sensor_data: " . $conn->error);
}

$average_node = "node_1";

$average_sql = "
    SELECT AVG(temperature) AS average_temperature,
           AVG(humidity) AS average_humidity
    FROM sensor_data
    WHERE node_name = '$average_node'
";

$average_result = $conn->query($average_sql);
$average_row = $average_result->fetch_assoc();

$average_temperature = $average_row["average_temperature"];
$average_humidity = $average_row["average_humidity"];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SSU IoT Lab</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e3f2fd;
            color: black;
            margin: 30px;
        }

        h1, h2 {
            text-align: center;
            color: black;
        }

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
            color: black;
        }

        th {
            background-color: #1976d2;
            color: white;
            padding: 12px;
            border: 1px solid black;
        }

        td {
            color: black;
            padding: 10px;
            border: 1px solid black;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #dbeeff;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .average {
            width: 90%;
            margin: 25px auto;
            padding: 15px;
            background-color: white;
            border: 1px solid black;
            text-align: center;
            font-size: 18px;
        }

        .average p {
            margin: 8px;
        }

        #chart-container {
            width: 90%;
            height: 450px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border: 1px solid black;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Welcome to SSU IoT Lab</h1>

    <h2>Registered Sensor Nodes</h2>

    <table>
        <tr>
            <th>Node Name</th>
            <th>Manufacturer</th>
            <th>Longitude</th>
            <th>Latitude</th>
        </tr>

        <?php while ($row = $register_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row["node_name"]) ?></td>
                <td><?= htmlspecialchars($row["manufacturer"]) ?></td>
                <td><?= htmlspecialchars($row["longitude"]) ?></td>
                <td><?= htmlspecialchars($row["latitude"]) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Data Received</h2>

    <table>
        <tr>
            <th>Node Name</th>
            <th>Time Received</th>
            <th>Temperature</th>
            <th>Humidity</th>
        </tr>

        <?php while ($row = $data_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row["node_name"]) ?></td>
                <td><?= htmlspecialchars($row["time_received"]) ?></td>
                <td><?= htmlspecialchars($row["temperature"]) ?> °C</td>
                <td><?= htmlspecialchars($row["humidity"]) ?> %</td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Average Values for Node 1</h2>

    <div class="average">
        <?php if ($average_temperature !== null): ?>
            <p>
                Average temperature:
                <?= number_format($average_temperature, 2) ?> °C
            </p>

            <p>
                Average humidity:
                <?= number_format($average_humidity, 2) ?> %
            </p>
        <?php else: ?>
            <p>No data is available for node_1.</p>
        <?php endif; ?>
    </div>

    <h2>Temperature Chart for Node 1</h2>

    <div id="chart-container">
        <canvas id="mycanvas"></canvas>
    </div>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
<script src="/Chartjs/app.js?v=<?= filemtime(__DIR__ . "/Chartjs/app.js") ?>"></script>

</body>
</html>

<?php
$conn->close();
?>
