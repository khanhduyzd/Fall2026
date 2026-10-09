#!/usr/bin/env python3

# ============================================================
# Title: Raspberry Pi Wi-Fi Button Controller
# ============================================================
# Program Detail:
# Purpose: Control Wi-Fi with two buttons and blink an LED
#          whenever wlan0 does not have an IPv4 address.
# Inputs: GPIO22 OFF button and GPIO23 ON button.
# Outputs: GPIO17 Wi-Fi status LED.
# Date: October 8, 2026
# Compiler: Python 3 interpreter on Raspberry Pi OS
# Author: Duy Pham
# Version:
#   V1.0 - Compact Wi-Fi controller
# ============================================================
# File Dependencies:
#   subprocess, time, RPi.GPIO, nmcli, ip
# ============================================================

import subprocess
import time
import RPi.GPIO as GPIO

LED = 17
OFF_BUTTON = 22
ON_BUTTON = 23

GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)
GPIO.setup(LED, GPIO.OUT, initial=GPIO.LOW)
GPIO.setup(OFF_BUTTON, GPIO.IN, pull_up_down=GPIO.PUD_DOWN)
GPIO.setup(ON_BUTTON, GPIO.IN, pull_up_down=GPIO.PUD_DOWN)


def run(*command):
    result = subprocess.run(
        command,
        capture_output=True,
        text=True
    )

    if result.returncode != 0:
        print("Error:", result.stderr.strip(), flush=True)

    return result.returncode == 0


def wifi_has_ip():
    result = subprocess.run(
        ["ip", "-4", "-o", "address", "show", "wlan0"],
        capture_output=True,
        text=True
    )

    return bool(result.stdout.strip())


def set_wifi(enabled):
    state = "on" if enabled else "off"
    print(f"{state.upper()} button detected", flush=True)

    if not run("nmcli", "radio", "wifi", state):
        return

    if enabled:
        time.sleep(2)
        run("nmcli", "device", "connect", "wlan0")

    print(f"Wi-Fi turned {state}", flush=True)


previous_off = GPIO.input(OFF_BUTTON)
previous_on = GPIO.input(ON_BUTTON)

led_state = False
has_ip = wifi_has_ip()
last_check = 0
last_blink = 0

print("PE4 running", flush=True)

try:
    while True:
        now = time.monotonic()
        off = GPIO.input(OFF_BUTTON)
        on = GPIO.input(ON_BUTTON)

        if off and not previous_off:
            set_wifi(False)

        if on and not previous_on:
            set_wifi(True)

        previous_off = off
        previous_on = on

        if now - last_check >= 0.5:
            has_ip = wifi_has_ip()
            last_check = now

        if not has_ip and now - last_blink >= 0.5:
            led_state = not led_state
            GPIO.output(LED, led_state)
            last_blink = now

        elif has_ip and led_state:
            led_state = False
            GPIO.output(LED, GPIO.LOW)

        time.sleep(0.02)

except KeyboardInterrupt:
    print("\nProgram stopped", flush=True)

finally:
    GPIO.output(LED, GPIO.LOW)
    GPIO.cleanup()
    subprocess.run(["nmcli", "radio", "wifi", "on"])
