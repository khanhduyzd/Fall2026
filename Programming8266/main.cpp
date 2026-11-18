/*
 * ----------------------------------------------
 * Project/Program Name : ESP8266 IoT Sensor System
 * File Name            : main.cpp
 * Author               : Duy Pham
 * Version              : 2.0
 *
 * Purpose:
 * Read temperature, humidity, and light data.
 * Send data through PHP when the pushbutton or
 * tilt switch is activated.
 * Retry failed transmissions every 30 seconds.
 *
 * ----------------------------------------------
 */

#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <DHT.h>

/* ---------- Pin assignments ---------- */

#define DHT_PIN       4       // GPIO4 / D2
#define DHT_TYPE      DHT11
#define TILT_PIN      12      // GPIO12 / D6
#define BUTTON_PIN    14      // GPIO14 / D5
#define LIGHT_PIN     A0

/* ---------- Wi-Fi information ---------- */

const char* WIFI_SSID = "Tupleutranh";
const char* WIFI_PASSWORD = "Caynhalavuon";

/* ---------- PHP server ---------- */

const char* PHP_URL =
    "https://seagreen-fox-591983.hostingersite.com/receive_sensor.php";

/* ---------- Objects ---------- */

DHT dht(DHT_PIN, DHT_TYPE);

/* ---------- Time-zone information ---------- */

String selectedTimeZone = "America/Los_Angeles";

/* ---------- Switch states ---------- */

bool previousTiltState = HIGH;
bool previousButtonState = HIGH;

/* ---------- Retransmission data ---------- */

struct PendingMeasurement {
    String nodeName;
    String timeReceived;
    float temperature;
    float humidity;
    int lightValue;
};

PendingMeasurement pendingMeasurement;

bool transmissionPending = false;
unsigned long lastAttemptTime = 0;
unsigned long lastWiFiReconnectTime = 0;

const unsigned long RETRY_INTERVAL = 60000;
const unsigned long WIFI_RECONNECT_INTERVAL = 10000;

/* =====================================================
   Connect to Wi-Fi
   ===================================================== */

bool connectToWiFi() {
    Serial.print("Connecting to Wi-Fi");

    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);
    WiFi.persistent(false);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    unsigned long startTime = millis();

    while (WiFi.status() != WL_CONNECTED &&
           millis() - startTime < 15000) {
        delay(500);
        Serial.print(".");
    }

    Serial.println();

    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("Wi-Fi connection failed.");
        Serial.println("The system will retry automatically.");
        return false;
    }

    Serial.println("Wi-Fi connected");

    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());

    Serial.print("MAC address: ");
    Serial.println(WiFi.macAddress());

    Serial.print("RSSI: ");
    Serial.print(WiFi.RSSI());
    Serial.println(" dBm");

    return true;
}

/* =====================================================
   Select time zone through Serial Monitor
   ===================================================== */

void selectTimeZone() {
    Serial.println();
    Serial.println("Select Your Time Zone.");
    Serial.println("Default is Pacific Time.");
    Serial.println("1. Eastern Time");
    Serial.println("2. Central Time");
    Serial.println("3. Mountain Time");
    Serial.println("4. Pacific Time");
    Serial.println("5. Alaska Time");
    Serial.println("6. Hawaii Time");
    Serial.println("7. Atlantic Time");
    Serial.println("Enter a number within 10 seconds:");

    unsigned long startTime = millis();

    while (millis() - startTime < 10000) {
        if (Serial.available()) {
            int choice = Serial.parseInt();

            switch (choice) {
                case 1:
                    selectedTimeZone = "America/New_York";
                    break;

                case 2:
                    selectedTimeZone = "America/Chicago";
                    break;

                case 3:
                    selectedTimeZone = "America/Denver";
                    break;

                case 4:
                    selectedTimeZone = "America/Los_Angeles";
                    break;

                case 5:
                    selectedTimeZone = "America/Anchorage";
                    break;

                case 6:
                    selectedTimeZone = "Pacific/Honolulu";
                    break;

                case 7:
                    selectedTimeZone = "America/Puerto_Rico";
                    break;

                default:
                    Serial.println("Invalid choice.");
                    Serial.println("Using Pacific Time.");
                    selectedTimeZone = "America/Los_Angeles";
                    break;
            }

            break;
        }

        delay(10);
    }

    Serial.print("Selected time zone: ");
    Serial.println(selectedTimeZone);
}

/* =====================================================
   Get current time from TimeAPI
   ===================================================== */

String getCurrentTime() {
    WiFiClientSecure client;
    client.setInsecure();

    HTTPClient http;

    String url =
        "https://timeapi.io/api/Time/current/zone?timeZone=" +
        selectedTimeZone;

    if (!http.begin(client, url)) {
        Serial.println("Could not connect to TimeAPI.");
        return "";
    }

    int httpCode = http.GET();

    if (httpCode != HTTP_CODE_OK) {
        Serial.print("TimeAPI error: ");
        Serial.println(httpCode);
        http.end();
        return "";
    }

    String response = http.getString();
    http.end();

    DynamicJsonDocument document(2048);

    DeserializationError error =
        deserializeJson(document, response);

    if (error) {
        Serial.println("Could not read TimeAPI response.");
        return "";
    }

    String dateTime = document["dateTime"].as<String>();

    // Change T to a space.
    dateTime.replace("T", " ");

    // Keep only YYYY-MM-DD HH:MM:SS.
    if (dateTime.length() > 19) {
        dateTime = dateTime.substring(0, 19);
    }

    Serial.print("Current time: ");
    Serial.println(dateTime);

    return dateTime;
}

/* =====================================================
   Send the stored measurement one time
   ===================================================== */

bool sendPendingMeasurement() {
    if (!transmissionPending) {
        return false;
    }

    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("Wi-Fi unavailable. Measurement remains pending.");
        return false;
    }

    if (pendingMeasurement.timeReceived == "") {
        pendingMeasurement.timeReceived = getCurrentTime();

        if (pendingMeasurement.timeReceived == "") {
            Serial.println(
                "Could not get timestamp. Measurement remains pending."
            );
            return false;
        }
    }

    String encodedTime = pendingMeasurement.timeReceived;

    // Encode the space in the timestamp.
    encodedTime.replace(" ", "%20");

    String url = String(PHP_URL) +
        "?nodeId=" + pendingMeasurement.nodeName +
        "&nodeTemp=" +
        String(pendingMeasurement.temperature, 2) +
        "&humidity=" +
        String(pendingMeasurement.humidity, 2) +
        "&light=" +
        String(pendingMeasurement.lightValue) +
        "&timeReceived=" +
        encodedTime;

    Serial.println();
    Serial.println("Sending measurement:");
    Serial.println(url);

    WiFiClientSecure client;
    client.setInsecure();

    HTTPClient http;

    if (!http.begin(client, url)) {
        Serial.println("Could not connect to PHP server.");
        return false;
    }

    int httpCode = http.GET();
    String response = http.getString();

    response.trim();

    Serial.print("HTTP response code: ");
    Serial.println(httpCode);

    Serial.print("Server response: ");
    Serial.println(response);

    http.end();

    /*
     * DUPLICATE means the server already stored the
     * measurement, so it is considered successful.
     */
    if (response == "SUCCESS" ||
        response == "DUPLICATE") {

        return true;
    }

    return false;
}

/* =====================================================
   Read sensors and create a new measurement
   ===================================================== */

void createMeasurement(String nodeName) {
    /*
     * Do not overwrite an unsent measurement.
     * The original values must be preserved for retry.
     */
    if (transmissionPending) {
        Serial.println();
        Serial.println(
            "Previous measurement is still waiting for delivery."
        );
        return;
    }

    float humidity = dht.readHumidity();
    float temperature = dht.readTemperature();
    int lightValue = analogRead(LIGHT_PIN);

    if (isnan(humidity) || isnan(temperature)) {
        Serial.println("ERROR: Could not read DHT11.");
        return;
    }


    /*
     * Store all original values.
     * These values will be reused during retransmission.
     */
    pendingMeasurement.nodeName = nodeName;
    pendingMeasurement.temperature = temperature;
    pendingMeasurement.humidity = humidity;
    pendingMeasurement.lightValue = lightValue;
    pendingMeasurement.timeReceived = "";

    transmissionPending = true;
    lastAttemptTime = millis();

    bool accepted = sendPendingMeasurement();

    if (accepted) {
        Serial.println("Measurement accepted.");
        transmissionPending = false;
    } else {
        Serial.println("Transmission failed.");
        Serial.println("Will retry in 30 seconds.");
    }
}

/* =====================================================
   Retry failed transmission every 30 seconds
   ===================================================== */

void checkRetransmission() {
    if (!transmissionPending) {
        return;
    }

    if (millis() - lastAttemptTime >= RETRY_INTERVAL) {
        Serial.println();
        Serial.println("Retrying previous measurement...");

        lastAttemptTime = millis();

        bool accepted = sendPendingMeasurement();

        if (accepted) {
            Serial.println("Retransmission accepted.");
            transmissionPending = false;
        } else {
            Serial.println("Retry failed.");
            Serial.println("Another retry will occur in 30 seconds.");
        }
    }
}

/* =====================================================
   Setup
   ===================================================== */

void setup() {
    Serial.begin(115200);
    delay(1000);

    dht.begin();

    pinMode(TILT_PIN, INPUT_PULLUP);
    pinMode(BUTTON_PIN, INPUT_PULLUP);

    // Prevent false activation immediately after startup.
    previousTiltState = digitalRead(TILT_PIN);
    previousButtonState = digitalRead(BUTTON_PIN);

    connectToWiFi();
    selectTimeZone();

    Serial.println();
    Serial.println("System ready.");
}

/* =====================================================
   Main loop
   ===================================================== */

void loop() {
    if (WiFi.status() != WL_CONNECTED &&
    millis() - lastWiFiReconnectTime >= WIFI_RECONNECT_INTERVAL) {

    lastWiFiReconnectTime = millis();

    Serial.println();
    Serial.println("Wi-Fi disconnected. Trying to reconnect...");
    WiFi.reconnect();
}
    
    bool currentTiltState = digitalRead(TILT_PIN);
    bool currentButtonState = digitalRead(BUTTON_PIN);
   
    /*
     * Detect a new tilt activation.
     * Tilt switch sends data as node_2.
     */
    if (previousTiltState == HIGH &&
        currentTiltState == LOW) {

        Serial.println();
        Serial.println("Tilt activated.");

        createMeasurement("node_2");

        delay(300);
    }

    /*
     * Detect a new pushbutton press.
     * Pushbutton sends data as node_1.
     */
    if (previousButtonState == HIGH &&
        currentButtonState == LOW) {

        Serial.println();
        Serial.println("Pushbutton pressed.");

        createMeasurement("node_1");

        delay(300);
    }

    // Check whether a failed measurement needs retrying.
    checkRetransmission();

    // Save current states for edge detection.
    previousTiltState = currentTiltState;
    previousButtonState = currentButtonState;

    delay(30);
}
