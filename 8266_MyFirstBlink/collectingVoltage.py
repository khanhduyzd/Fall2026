/*
;============================================================
; Title: ESP8266 Battery Voltage Data Logger
;============================================================
; Program Detail:
; Purpose: Measure LiPo battery voltage every 60 seconds.
; Inputs: Analog voltage-divider output connected to A0.
; Outputs: CSV data through the serial monitor.
; Date: October 3, 2026
; Compiler: PlatformIO with Arduino ESP8266 framework
; Author: Duy Pham
; Version:
;   V1.0 - Initial automatic battery-voltage logger
;============================================================
; File Dependencies:
;   Arduino.h
;============================================================
*/

#include <Arduino.h>

// Measurement interval required by the assignment.
const unsigned long SAMPLE_INTERVAL_MS = 60000UL;

// Number of rapid ADC readings averaged for each recorded measurement.
const unsigned int ADC_SAMPLES = 20;

// External voltage-divider resistors.
const float R1 = 36000.0;  // Battery positive to A0 junction
const float R2 = 10000.0;  // A0 junction to ground

const float DIVIDER_FACTOR = (R1 + R2) / R2;

// Temporary one-point calibration:
// Multimeter = 3.78 V when average ADC reading = 277.
//
// Replace this after completing the required two-point calibration.
const float CALIBRATION_BATTERY_VOLTAGE = 3.78;
const float CALIBRATION_ADC_READING = 277.0;

// Calibrated voltage at the A0 junction per ADC count.
const float A0_VOLTS_PER_COUNT =
    (CALIBRATION_BATTERY_VOLTAGE / DIVIDER_FACTOR) /
    CALIBRATION_ADC_READING;

unsigned long sampleNumber = 0;
unsigned long previousSampleTime = 0;

float readAverageADC() {
  unsigned long total = 0;

  // Discard the first reading.
  analogRead(A0);
  delay(5);

  for (unsigned int i = 0; i < ADC_SAMPLES; i++) {
    total += analogRead(A0);
    delay(10);
  }

  return total / static_cast<float>(ADC_SAMPLES);
}

void recordMeasurement() {
  float averageADC = readAverageADC();

  float voltageAtA0 =
      averageADC * A0_VOLTS_PER_COUNT;

  float batteryVoltage =
      voltageAtA0 * DIVIDER_FACTOR;

  float elapsedMinutes =
      millis() / 60000.0;

  // Print one CSV row.
  Serial.print(sampleNumber);
  Serial.print(",");
  Serial.print(elapsedMinutes, 3);
  Serial.print(",");
  Serial.print(averageADC, 1);
  Serial.print(",");
  Serial.println(batteryVoltage, 3);

  sampleNumber++;
}

void setup() {
  Serial.begin(9600);
  delay(1000);

  // CSV column headings.
  Serial.println(
      "sample,elapsed_min,adc_average,battery_voltage_V"
  );

  // Record the initial measurement immediately.
  recordMeasurement();
  previousSampleTime = millis();
}

void loop() {
  unsigned long currentTime = millis();

  if (currentTime - previousSampleTime >= SAMPLE_INTERVAL_MS) {
    // Adding the interval maintains a consistent schedule.
    previousSampleTime += SAMPLE_INTERVAL_MS;
    recordMeasurement();
  }
}
