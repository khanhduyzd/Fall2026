<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$servername = "localhost";
$username = "u153404388_db_DuyPham";
$password = "Database2026!";
$dbname = "u153404388_DuyPham";
$conn = new mysqli(
    $servername,
    $username,
    $password,
    $dbname
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/* Registered sensor nodes */
$register_sql = "
    SELECT node_name, manufacturer, longitude, latitude
    FROM sensor_register
    ORDER BY node_name ASC
";

$register_result = $conn->query($register_sql);

if (!$register_result) {
    die("Error reading sensor_register: " . $conn->error);
}

/* Sensor data sorted by node name and time */
$data_sql = "
    SELECT node_name, time_received, temperature, humidity
    FROM sensor_data
    ORDER BY node_name ASC, time_received ASC
";

$data_result = $conn->query($data_sql);

if (!$data_result) {
    die("Error reading sensor_data: " . $conn->error);
}

/* Calculate averages for node_1 */
$average_node = "node_1";

$average_sql = "
    SELECT
        AVG(temperature) AS average_temperature,
        AVG(humidity) AS average_humidity
    FROM sensor_data
    WHERE node_name = '$average_node'
";

$average_result = $conn->query($average_sql);

if (!$average_result) {
    die("Error calculating averages: " . $conn->error);
}

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
            color: black;
            border: 1px solid black;
            text-align: center;
            font-size: 18px;
        }

        .average p {
            margin: 8px;
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

        <?php
        if ($register_result->num_rows > 0) {
            while ($row = $register_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["node_name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["manufacturer"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["longitude"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["latitude"]) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr>";
            echo "<td colspan='4'>No registered sensors found.</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Data Received</h2>

    <table>
        <tr>
            <th>Node Name</th>
            <th>Time Received</th>
            <th>Temperature</th>
            <th>Humidity</th>
        </tr>

        <?php
        if ($data_result->num_rows > 0) {
            while ($row = $data_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["node_name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["time_received"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["temperature"]) . " °C</td>";
                echo "<td>" . htmlspecialchars($row["humidity"]) . " %</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr>";
            echo "<td colspan='4'>No sensor data found.</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Average Values</h2>

    <div class="average">
        <?php if ($average_temperature !== null): ?>

            <p>
                The average temperature for
                <strong><?= htmlspecialchars($average_node) ?></strong>
                is:

                <?= number_format($average_temperature, 2) ?> °C
            </p>

            <p>
                The average humidity for
                <strong><?= htmlspecialchars($average_node) ?></strong>
                is:

                <?= number_format($average_humidity, 2) ?> %
            </p>

        <?php else: ?>

            <p>No data is available for <?= htmlspecialchars($average_node) ?>.</p>

        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>
