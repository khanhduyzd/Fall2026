//-----------------------------
// Title: MQTT
//-----------------------------
// Program Details:
//-----------------------------
// Purpose: Conenct to broker.mqtt-dashboard.com, Publish and subscribe
// Dependencies: Make sure you have installed PubSubClient.h
// Compiler: PIO Version 1.72.0
// Atuhor: Originally an example called ESP8266-mqtt / slightly modified and cleaned up by Farid Farahmand
// OUTPUT: publishes 1,2,3,.... on outTopic every publishTimeInterval
// INPUT: Received value from the broker on inTopic  
// SETUP: To see the published values go to http://www.hivemq.com/demos/websocket-client/ 
//        subscribe to inTopic and outTopic. You can also create an APP using MQTT Dash
// Versions: 
//  v1: Nov-24-2022 - Cleaned up version 
//-----------------------------

#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <PubSubClient.h>

const char* ssid = "wwifi_name";   // Wi-Fi settings
const char* password = "password";

const char* mqtt_server = "broker.hivemq.com"; // MQTT settings
const int mqtt_port = 1883;

const char* publishTopic =  "testtopic/temp/outTopic/esp8266"; // ESP publishes potentiometer and button values here
const char* subscribeTopic =  "testtopic/temp/inTopic"; // ESP receives LED commands here
const char* ledStateTopic = "testtopic/temp/ledState/esp8266"; // ESP reports the current LED state here

// -----------------------------
// Pin definitions
// -----------------------------
#define BUILTIN_LED 2  // D4 / GPIO2
#define BUTTON_PIN 5   // D1 / GPIO5

// -----------------------------
// Timing settings
// -----------------------------
const unsigned long SENSOR_PRINT_INTERVAL_SECONDS = 1;
const unsigned long POTENTIOMETER_INTERVAL_SECONDS = 15;
const unsigned long BUTTON_RELEASE_INTERVAL_SECONDS = 5;

// -----------------------------
// Variables
// -----------------------------
WiFiClient espClient;
PubSubClient client(espClient);

unsigned long lastSensorPrint = 0;
unsigned long lastPotentiometerPublish = 0;
unsigned long buttonPressedTime = 0;

bool previousButtonState = false;
bool releaseMessagePending = false;
bool ledStatus = false;

char messageBuffer[30];

// -----------------------------
// Set up Wi-Fi
// -----------------------------
void setup_wifi() {
  delay(10);

  Serial.println();
  Serial.print("Connecting to Wi-Fi: ");
  Serial.println(ssid);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.println("Wi-Fi connected");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}

// -----------------------------
// Control the built-in LED
// -----------------------------
void setLed(bool turnOn) {
  ledStatus = turnOn;

  // The built-in ESP8266 LED is active-low
  if (turnOn) {
    digitalWrite(BUILTIN_LED, LOW);
  } else {
    digitalWrite(BUILTIN_LED, HIGH);
  }

  Serial.print("LED state: ");
  Serial.println(turnOn ? "ON" : "OFF");

  // Report LED state to the MQTT broker
  if (client.connected()) {
    client.publish(
        ledStateTopic,
        turnOn ? "1" : "0",
        true
    );
  }
}

// -----------------------------
// Publish a value to MQTT
// -----------------------------
void publishValue(const char* value) {
  if (client.connected()) {
    bool success = client.publish(publishTopic, value);

    if (success) {
      Serial.print("Published to ");
      Serial.print(publishTopic);
      Serial.print(": ");
      Serial.println(value);
    } else {
      Serial.println("MQTT publish failed");
    }
  } else {
    Serial.println("Cannot publish: MQTT is disconnected");
  }
}

// -----------------------------
// MQTT callback
// -----------------------------
void callback(char* topic, byte* payload, unsigned int length) {
  String message = "";

  for (unsigned int i = 0; i < length; i++) {
    message += (char)payload[i];
  }

  message.trim();

  Serial.print("MQTT message received from ");
  Serial.print(topic);
  Serial.print(": ");
  Serial.println(message);

  if (message == "1") {
    setLed(true);
  } else if (message == "0") {
    setLed(false);
  }
}

// -----------------------------
// Connect or reconnect to MQTT
// -----------------------------
void reconnect_mqtt() {
  while (!client.connected()) {
    Serial.print("Connecting to MQTT broker...");

    String clientId = "ESP8266Client-";
    clientId += String(ESP.getChipId(), HEX);

    if (client.connect(clientId.c_str())) {
      Serial.println("connected");

      client.subscribe(subscribeTopic);

      Serial.print("Subscribed to: ");
      Serial.println(subscribeTopic);

      // Send the current LED state after reconnecting
      client.publish(
          ledStateTopic,
          ledStatus ? "1" : "0",
          true
      );
    } else {
      Serial.print("MQTT failed, state=");
      Serial.print(client.state());
      Serial.println(". Trying again in 5 seconds.");

      delay(5000);
    }
  }
}

// -----------------------------
// Setup
// -----------------------------
void setup() {
  Serial.begin(9600);

  pinMode(BUILTIN_LED, OUTPUT);
  pinMode(BUTTON_PIN, INPUT_PULLUP);

  // Built-in LED starts OFF
  digitalWrite(BUILTIN_LED, HIGH);

  setup_wifi();

  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(callback);

  Serial.println("System started");
}

// -----------------------------
// Main loop
// -----------------------------
void loop() {
  // Reconnect Wi-Fi if necessary
  if (WiFi.status() != WL_CONNECTED) {
    setup_wifi();
  }

  // Reconnect MQTT if necessary
  if (!client.connected()) {
    reconnect_mqtt();
  }

  // Keep MQTT communication active
  client.loop();

  // Convert milliseconds to seconds
  unsigned long nowSeconds = millis() / 1000UL;

  // -----------------------------
  // Read button
  // -----------------------------
  bool buttonIsPressed =
      digitalRead(BUTTON_PIN) == LOW;

  // Detect a new button press
  if (buttonIsPressed && !previousButtonState) {
    Serial.println("Button pressed");

    // Publish 1 when button is pressed
    publishValue("1");

    buttonPressedTime = nowSeconds;
    releaseMessagePending = true;
  }

  // Publish 0 five seconds after the press
  if (releaseMessagePending &&
      nowSeconds - buttonPressedTime >=
          BUTTON_RELEASE_INTERVAL_SECONDS) {
    Serial.println("Five seconds passed");

    publishValue("0");

    releaseMessagePending = false;
  }

  previousButtonState = buttonIsPressed;

  // -----------------------------
  // Print sensor values
  // -----------------------------
  if (nowSeconds - lastSensorPrint >=
      SENSOR_PRINT_INTERVAL_SECONDS) {
    lastSensorPrint = nowSeconds;

    int potentiometerValue = analogRead(A0);
    int buttonValue = buttonIsPressed ? 1 : 0;

    Serial.print("Potentiometer: ");
    Serial.print(potentiometerValue);

    Serial.print("    Button: ");
    Serial.println(buttonValue);
  }

  // -----------------------------
  // Publish potentiometer value
  // -----------------------------
  if (nowSeconds - lastPotentiometerPublish >=
      POTENTIOMETER_INTERVAL_SECONDS) {
    lastPotentiometerPublish = nowSeconds;

    int potentiometerValue = analogRead(A0);

    snprintf(
        messageBuffer,
        sizeof(messageBuffer),
        "%d",
        potentiometerValue
    );

    publishValue(messageBuffer);
  }
}
