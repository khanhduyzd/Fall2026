<?php
/*
 * ESP8266 Sensor Data Receiver
 *
 * Required parameters:
 * nodeId
 * nodeTemp
 * humidity
 * light
 * timeReceived
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);

header("Content-Type: text/plain");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username   = "YOUR_DATABASE_USERNAME";
$password   = "YOUR_NEW_DATABASE_PASSWORD";
$dbname     = "YOUR_DATABASE_NAME";

$conn = null;

try {
    // Accept only GET and POST requests.
    if ($_SERVER["REQUEST_METHOD"] !== "GET" &&
        $_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);
        exit("ERROR: Only GET and POST requests are accepted");
    }

    // Combine GET and POST data.
    $input = array_merge($_GET, $_POST);

    $nodeId = trim(
        $input["nodeId"] ??
        $input["node_name"] ??
        ""
    );

    $temperature =
        $input["nodeTemp"] ??
        $input["temperature"] ??
        "";

    $humidity = $input["humidity"] ?? "";

    $light =
        $input["light"] ??
        $input["lightValue"] ??
        "";

    $timeReceived = trim(
        $input["timeReceived"] ??
        $input["time_received"] ??
        ""
    );

    // All values are required.
    if ($nodeId === "" ||
        $temperature === "" ||
        $humidity === "" ||
        $light === "" ||
        $timeReceived === "") {

        http_response_code(400);
        exit(
            "ERROR: nodeId, nodeTemp, humidity, light, " .
            "and timeReceived are required"
        );
    }

    // Validate node name, such as node_1 or node_2.
    if (!preg_match("/^node_[0-9]+$/", $nodeId)) {
        http_response_code(400);
        exit("ERROR: Invalid node name");
    }

    // Validate temperature.
    if (!is_numeric($temperature) ||
        $temperature < -10 ||
        $temperature > 100) {

        http_response_code(400);
        exit("ERROR: Invalid temperature");
    }

    // Validate humidity.
    if (!is_numeric($humidity) ||
        $humidity < 0 ||
        $humidity > 100) {

        http_response_code(400);
        exit("ERROR: Invalid humidity");
    }

    // ESP8266 analogRead() normally returns 0 to 1023.
    if (!is_numeric($light) ||
        $light < 0 ||
        $light > 1023) {

        http_response_code(400);
        exit("ERROR: Invalid light value");
    }

    // Validate timestamp format: YYYY-MM-DD HH:MM:SS.
    $date = DateTime::createFromFormat(
        "Y-m-d H:i:s",
        $timeReceived
    );

    $dateErrors = DateTime::getLastErrors();

    if ($date === false ||
        ($dateErrors !== false &&
        ($dateErrors["warning_count"] > 0 ||
         $dateErrors["error_count"] > 0)) ||
        $date->format("Y-m-d H:i:s") !== $timeReceived) {

        http_response_code(400);
        exit(
            "ERROR: Timestamp must use " .
            "YYYY-MM-DD HH:MM:SS"
        );
    }

    $temperature  = (float)$temperature;
    $humidity     = (float)$humidity;
    $light        = (int)$light;
    $timeReceived = $date->format("Y-m-d H:i:s");

    // Connect to the database.
    $conn = new mysqli(
        $servername,
        $username,
        $password,
        $dbname
    );

    $conn->set_charset("utf8mb4");
    $conn->begin_transaction();

    // Confirm that the node is registered.
    $stmt = $conn->prepare(
        "SELECT node_name
         FROM sensor_register
         WHERE node_name = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $nodeId);
    $stmt->execute();

    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        $conn->rollback();
        http_response_code(403);
        exit("ERROR: Node is not registered");
    }

    // Insert the new sensor measurement.
    $stmt = $conn->prepare(
        "INSERT INTO sensor_data
         (node_name, time_received, temperature, humidity, light)
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssddi",
        $nodeId,
        $timeReceived,
        $temperature,
        $humidity,
        $light
    );

    try {
        $stmt->execute();
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        $stmt->close();

        // Duplicate node and timestamp means delivery succeeded earlier.
        if ($e->getCode() == 1062) {
            $conn->rollback();
            http_response_code(200);
            exit("DUPLICATE");
        }

        throw $e;
    }

    // Increase the counter only after successful insertion.
    $stmt = $conn->prepare(
        "INSERT INTO sensor_activity
         (node_name, valid_count)
         VALUES (?, 1)
         ON DUPLICATE KEY UPDATE
         valid_count = valid_count + 1"
    );

    $stmt->bind_param("s", $nodeId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $conn->close();

    http_response_code(200);
    echo "SUCCESS";

} catch (Throwable $e) {
    if ($conn !== null) {
        try {
            $conn->rollback();
            $conn->close();
        } catch (Throwable $ignored) {
            // Ignore cleanup errors.
        }
    }

    error_log($e->getMessage());

    http_response_code(500);
    echo "ERROR: Server or database failure";
}
?>
