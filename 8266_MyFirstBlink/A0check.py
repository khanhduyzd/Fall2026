#include <Arduino.h>

const float VOLTS_PER_COUNT = 3.78 / 277.0;

void setup() {
  Serial.begin(9600);
}

void loop() {
  int adcRaw = analogRead(A0);
  float batteryVoltage = adcRaw * VOLTS_PER_COUNT;

  
  Serial.println(batteryVoltage, 3);
  

  delay(1000);
}
