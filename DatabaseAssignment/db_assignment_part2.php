<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

/* Sensor data sorted by node name and time */
$data_sql = "
    SELECT node_name, time_received, temperature, humidity
    FROM sensor_data
    ORDER BY node_name ASC, time_received ASC
";

$data_result = $conn->query($data_sql);
?>

<!DOCTYPE html>
<html>
<head>
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
        border: 1px solid #1976d2;
        text-align: center;
    }

    tr:nth-child(even) {
        background-color: #dbeeff;
    }

    .container {
        max-width: 1100px;
        margin: auto;
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
            echo "<tr><td colspan='4'>No registered sensors found.</td></tr>";
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
            echo "<tr><td colspan='4'>No sensor data found.</td></tr>";
        }
        ?>
    </table>

</div>

</body>
</html>

<?php
$conn->close();
?>
