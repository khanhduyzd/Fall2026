#include <Arduino.h>

void setup() {
  pinMode(LED_BUILTIN, OUTPUT);
  Serial.begin(9600);
}

void loop() {
  // ESP8266 built-in LED is active-low.
  digitalWrite(LED_BUILTIN, LOW);
  Serial.println("LED is ON");
  delay(1000);

  digitalWrite(LED_BUILTIN, HIGH);
  Serial.println("LED is OFF");
  delay(2000);
}
