<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$is_base64_request = false;

/*
   Detect normal GET/POST request or Base64 request
*/
if (isset($_GET["nodeId"]) || isset($_GET["node_name"]) ||
    isset($_POST["nodeId"]) || isset($_POST["node_name"])) {

    /*
       Normal GET or POST request
    */
    $input = array_merge($_GET, $_POST);

    $nodeId = $input["nodeId"] ?? $input["node_name"] ?? "";
    $nodeTemp = $input["nodeTemp"] ?? $input["temperature"] ?? "";
    $timeReceived = $input["timeReceived"] ?? $input["time_received"] ?? "";
    $humidity = $input["humidity"] ?? "";

} else {

    /*
       Base64 request

       Example decoded message:
       nodeId=node_3&nodeTemp=34&timeReceived=2022-10-01%2010:25:01
    */
    $is_base64_request = true;

    $encoded_message = $_SERVER["QUERY_STRING"];

    $decoded_message = base64_decode($encoded_message, true);

    if ($decoded_message === false) {
        die("Error: Invalid Base64 message.");
    }

    parse_str($decoded_message, $decoded_values);

    $nodeId = $decoded_values["nodeId"] ?? "";
    $nodeTemp = $decoded_values["nodeTemp"] ?? "";
    $timeReceived = $decoded_values["timeReceived"] ?? "";
    $humidity = $decoded_values["humidity"] ?? "";

    /*
       Base64 requests must include time
    */
    if ($nodeId === "" || $nodeTemp === "" || $timeReceived === "") {
        die("Error: Base64 message must contain nodeId, nodeTemp, and timeReceived.");
    }

    echo "<h2>Decoded Values</h2>";
    echo "nodeId = " . htmlspecialchars($nodeId) . "<br>";
    echo "nodeTemp = " . htmlspecialchars($nodeTemp) . "<br>";
    echo "timeReceived = " . htmlspecialchars($timeReceived) . "<br>";

    if ($humidity !== "") {
        echo "humidity = " . htmlspecialchars($humidity) . "<br>";
    }
}

/*
   Database connection
*/
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

/*
   Check request method
*/
if ($_SERVER["REQUEST_METHOD"] !== "GET" &&
    $_SERVER["REQUEST_METHOD"] !== "POST") {

    die("Only GET and POST requests are accepted.");
}

/*
   Normalize the values.
   These variables are used for all later validation and insertion.
*/
$node_name = trim($nodeId);
$temperature = $nodeTemp;
$time_received = trim($timeReceived);

if ($humidity === "") {
    $humidity = null;
}

/*
   Check required values
*/
if ($node_name === "" || $temperature === "") {
    die("Error: nodeId and nodeTemp are required.");
}

/*
   Check node-name format
*/
if (!preg_match("/^node_[0-9]+$/", $node_name)) {
    die("Error: node name must be node_1, node_2, node_3, etc.");
}

/*
   Check temperature range
*/
if (!is_numeric($temperature) ||
    $temperature < -10 ||
    $temperature > 100) {

    die("Error: temperature must be between -10 and 100 Celsius.");
}

$temperature = (float) $temperature;

/*
   Check humidity range
*/
if ($humidity !== null) {

    if (!is_numeric($humidity) ||
        $humidity < 0 ||
        $humidity > 100) {

        die("Error: humidity must be between 0 and 100 percent.");
    }

    $humidity = (float) $humidity;
}

/*
   Check whether the node is registered
*/
$check_node = $conn->prepare(
    "SELECT node_name
     FROM sensor_register
     WHERE node_name = ?"
);

$check_node->bind_param("s", $node_name);
$check_node->execute();

$node_result = $check_node->get_result();

if ($node_result->num_rows === 0) {
    die("Error: this sensor node is not registered.");
}

$check_node->close();

/*
   Validate the time if one was provided
*/
$formatted_time = null;

if ($time_received !== "") {

    $date = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $time_received
    );

    if (!$date) {
        $date = DateTime::createFromFormat(
            "n-j-Y H:i:s",
            $time_received
        );
    }

    if (!$date) {
        die("Error: use time format YYYY-MM-DD HH:MM:SS.");
    }

    $formatted_time = $date->format("Y-m-d H:i:s");
}

/*
   Insert the data.

   If time is missing, time_received is not included in the
   INSERT statement. The database then uses CURRENT_TIMESTAMP.
*/
try {

    if ($formatted_time !== null && $humidity !== null) {

        $sql = "
            INSERT INTO sensor_data
            (node_name, time_received, temperature, humidity)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssdd",
            $node_name,
            $formatted_time,
            $temperature,
            $humidity
        );

    } elseif ($formatted_time !== null) {

        $sql = "
            INSERT INTO sensor_data
            (node_name, time_received, temperature)
            VALUES (?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssd",
            $node_name,
            $formatted_time,
            $temperature
        );

    } elseif ($humidity !== null) {

        $sql = "
            INSERT INTO sensor_data
            (node_name, temperature, humidity)
            VALUES (?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sdd",
            $node_name,
            $temperature,
            $humidity
        );

    } else {

        $sql = "
            INSERT INTO sensor_data
            (node_name, temperature)
            VALUES (?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sd",
            $node_name,
            $temperature
        );
    }

    $stmt->execute();

    echo "<h2>Data inserted successfully.</h2>";
    echo "Node: " . htmlspecialchars($node_name) . "<br>";
    echo "Temperature: " . htmlspecialchars($temperature) . " °C<br>";

    if ($humidity !== null) {
        echo "Humidity: " . htmlspecialchars($humidity) . " %<br>";
    }

    if ($formatted_time !== null) {
        echo "Time: " . htmlspecialchars($formatted_time) . "<br>";
    } else {
        echo "Time: Automatically generated by the database<br>";
    }

    $stmt->close();

} catch (mysqli_sql_exception $e) {

    if ($e->getCode() == 1062) {
        echo "Error: duplicate node and time combination. This data was not inserted.";
    } else {
        echo "Database error: " . htmlspecialchars($e->getMessage());
    }
}

$conn->close();
?>
