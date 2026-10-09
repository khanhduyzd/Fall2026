#!/usr/bin/env python3

# ============================================================
# Title: Raspberry Pi Wi-Fi Control and IP Status Indicator
# ============================================================
# Program Detail:
# Purpose: Turn Wi-Fi off or on using two push buttons and
#          blink an LED whenever wlan0 has no IPv4 address.
# Inputs:
#   GPIO22 - Wi-Fi OFF button
#   GPIO23 - Wi-Fi ON button
# Outputs:
#   GPIO17 - Wi-Fi status LED
# Date: October 8, 2026
# Compiler: Python 3 interpreter on Raspberry Pi OS
# Author: Duy Pham
# Version:
#   V1.0 - Initial Wi-Fi control and status indicator
# ============================================================
# File Dependencies:
#   subprocess
#   time
#   RPi.GPIO
#   NetworkManager nmcli utility
#   Linux ip utility
# ============================================================

import subprocess
import time
import RPi.GPIO as GPIO

LED_PIN = 17
OFF_BUTTON_PIN = 22
ON_BUTTON_PIN = 23

GPIO.setwarnings(False)
GPIO.setmode(GPIO.BCM)

GPIO.setup(LED_PIN, GPIO.OUT, initial=GPIO.LOW)

GPIO.setup(
    OFF_BUTTON_PIN,
    GPIO.IN,
    pull_up_down=GPIO.PUD_DOWN
)

GPIO.setup(
    ON_BUTTON_PIN,
    GPIO.IN,
    pull_up_down=GPIO.PUD_DOWN
)


def wifi_has_ip():
    """Return True when wlan0 has an IPv4 address."""

    result = subprocess.run(
        [
            "ip",
            "-4",
            "-o",
            "address",
            "show",
            "dev",
            "wlan0",
            "scope",
            "global"
        ],
        capture_output=True,
        text=True,
        check=False
    )

    return bool(result.stdout.strip())


def set_wifi(enabled):
    """Enable or disable the Wi-Fi interface."""

    if enabled:
        print("ON button detected", flush=True)

        radio_result = subprocess.run(
            ["nmcli", "radio", "wifi", "on"],
            capture_output=True,
            text=True,
            check=False
        )

        if radio_result.returncode != 0:
            print(
                "Unable to enable Wi-Fi:",
                radio_result.stderr.strip(),
                flush=True
            )
            return

        # Give the Wi-Fi interface time to become available.
        time.sleep(2)

        connect_result = subprocess.run(
            ["nmcli", "device", "connect", "wlan0"],
            capture_output=True,
            text=True,
            check=False
        )

        if connect_result.returncode != 0:
            print(
                "Wi-Fi enabled, but connection request reported:",
                connect_result.stderr.strip(),
                flush=True
            )
        else:
            print("Wi-Fi enabled; connecting...", flush=True)

    else:
        print("OFF button detected", flush=True)

        result = subprocess.run(
            ["nmcli", "radio", "wifi", "off"],
            capture_output=True,
            text=True,
            check=False
        )

        if result.returncode == 0:
            print("Wi-Fi disabled", flush=True)
        else:
            print(
                "Unable to disable Wi-Fi:",
                result.stderr.strip(),
                flush=True
            )


previous_off_state = GPIO.input(OFF_BUTTON_PIN)
previous_on_state = GPIO.input(ON_BUTTON_PIN)

led_state = False
last_blink_time = 0
last_ip_check = 0
has_ip = wifi_has_ip()

try:
    print("PE4 program running", flush=True)

    while True:
        current_time = time.monotonic()

        off_state = GPIO.input(OFF_BUTTON_PIN)
        on_state = GPIO.input(ON_BUTTON_PIN)

        # Detect one press of the OFF button.
        if off_state == GPIO.HIGH and previous_off_state == GPIO.LOW:
            set_wifi(False)

        # Detect one press of the ON button.
        if on_state == GPIO.HIGH and previous_on_state == GPIO.LOW:
            set_wifi(True)

        previous_off_state = off_state
        previous_on_state = on_state

        # Check the Wi-Fi IP address twice per second.
        if current_time - last_ip_check >= 0.5:
            has_ip = wifi_has_ip()
            last_ip_check = current_time

        # Blink while wlan0 does not have an IP address.
        if not has_ip:
            if current_time - last_blink_time >= 0.5:
                led_state = not led_state
                GPIO.output(LED_PIN, led_state)
                last_blink_time = current_time

        # Turn the LED off when wlan0 has an IP address.
        elif led_state:
            GPIO.output(LED_PIN, GPIO.LOW)
            led_state = False

        time.sleep(0.02)

except KeyboardInterrupt:
    print("\nProgram stopped", flush=True)

finally:
    GPIO.output(LED_PIN, GPIO.LOW)
    GPIO.cleanup()

    # Leave Wi-Fi enabled when the program exits.
    subprocess.run(
        ["nmcli", "radio", "wifi", "on"],
        check=False
    )
