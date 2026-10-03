#include <Arduino.h>

const float ADC_MAX_VOLTAGE = 1.0;
const float R1 = 36000.0;
const float R2 = 10000.0;

void setup() {
  Serial.begin(9600);
}

void loop() {
  int adcRaw = analogRead(A0);

  float adcVoltage =
      adcRaw * ADC_MAX_VOLTAGE / 1023.0;

  float batteryVoltage =
      adcVoltage * (R1 + R2) / R2;

  Serial.print("Battery voltage: ");
  Serial.print(batteryVoltage, 3);
  Serial.println(" V");

  delay(1000);  // Change to 60000 for the final experiment
}
