#!/usr/bin/env python3

# ============================================================
# Title: Raspberry Pi Wi-Fi Control and IP Status Indicator
# ============================================================
# Program Detail:
# Purpose: Control Wi-Fi with two external buttons and blink
#          an LED when wlan0 has no valid IPv4 address.
# Inputs: Buttons connected to BCM GPIO22 and BCM GPIO23.
# Outputs: Wi-Fi state and LED connected to BCM GPIO17.
# Date: October 8, 2026
# Compiler: Python 3 interpreter on Raspberry Pi OS
# Author: Duy Pham
# Version:
#   V1.0 - Initial Wi-Fi control and IP-status indicator
# ============================================================
# File Dependencies:
#   subprocess
#   time
#   RPi.GPIO
#   nmcli
#   ip
# ============================================================

import subprocess
import time
import RPi.GPIO as GPIO

LED = 17
WIFI_OFF = 22
WIFI_ON = 23

GPIO.setmode(GPIO.BCM)
GPIO.setup(LED, GPIO.OUT, initial=GPIO.LOW)
GPIO.setup(WIFI_OFF, GPIO.IN, pull_up_down=GPIO.PUD_DOWN)
GPIO.setup(WIFI_ON, GPIO.IN, pull_up_down=GPIO.PUD_DOWN)


def set_wifi(enabled):
    """Enable or disable the Wi-Fi radio."""
    state = "on" if enabled else "off"

    subprocess.run(
        ["nmcli", "radio", "wifi", state],
        check=False
    )

    print(
        "Wi-Fi enabled" if enabled else "Wi-Fi disabled",
        flush=True
    )


def wifi_has_ip():
    """Check whether wlan0 has a valid IPv4 address."""
    result = subprocess.run(
        [
            "ip", "-4", "-o", "addr", "show",
            "dev", "wlan0", "scope", "global"
        ],
        stdout=subprocess.PIPE,
        stderr=subprocess.DEVNULL,
        text=True,
        check=False
    )

    return bool(result.stdout.strip())


previous_off = GPIO.input(WIFI_OFF)
previous_on = GPIO.input(WIFI_ON)

has_ip = wifi_has_ip()
led_state = False

last_ip_check = 0
last_blink = 0

try:
    while True:
        now = time.monotonic()

        off_pressed = GPIO.input(WIFI_OFF)
        on_pressed = GPIO.input(WIFI_ON)

        # Detect a new press of the OFF button.
        if off_pressed and not previous_off:
            set_wifi(False)

        # Detect a new press of the ON button.
        if on_pressed and not previous_on:
            set_wifi(True)

        previous_off = off_pressed
        previous_on = on_pressed

        # Check the Wi-Fi IP address twice per second.
        if now - last_ip_check >= 0.5:
            has_ip = wifi_has_ip()
            last_ip_check = now

        # Blink when wlan0 has no IPv4 address.
        if not has_ip:
            if now - last_blink >= 0.5:
                led_state = not led_state
                GPIO.output(LED, led_state)
                last_blink = now

        # Keep the LED off while wlan0 has an address.
        elif led_state:
            GPIO.output(LED, GPIO.LOW)
            led_state = False

        time.sleep(0.02)

except KeyboardInterrupt:
    pass

finally:
    GPIO.output(LED, GPIO.LOW)
    GPIO.cleanup()

    # Restore Wi-Fi when the program ends.
    subprocess.run(
        ["nmcli", "radio", "wifi", "on"],
        check=False
    )
