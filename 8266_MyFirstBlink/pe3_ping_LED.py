# ============================================================
# Title: Raspberry Pi Incoming Ping LED Indicator
# ============================================================
# Program Detail:
# Purpose: Detect incoming IPv4 ping requests and control an LED.
# Inputs: ICMP echo requests captured from the network.
# Outputs: LED connected to BCM GPIO17 and terminal messages.
# Date: October 8, 2026
# Compiler: Python 3 interpreter on Raspberry Pi OS
# Author: Duy Pham
# Version:
#   V1.0 - Initial incoming-ping LED indicator
# ============================================================
# File Dependencies:
#   select
#   subprocess
#   time
#   RPi.GPIO
#   tcpdump
# ============================================================
#!/usr/bin/env python3

import select
import subprocess
import time
import RPi.GPIO as GPIO

LED = 17
OFF_DELAY = 3

GPIO.setmode(GPIO.BCM)
GPIO.setup(LED, GPIO.OUT, initial=GPIO.LOW)

capture = subprocess.Popen(
    [
        "tcpdump", "-l", "-n", "-i", "any",
        "icmp and icmp[0] == 8 and not src host 192.168.1.254"
    ],
    stdout=subprocess.PIPE,
    stderr=subprocess.DEVNULL,
    text=True
)

led_on = False
last_ping = 0

try:
    while True:
        if capture.poll() is not None:
            raise RuntimeError("tcpdump stopped unexpectedly")

        ready, _, _ = select.select(
            [capture.stdout], [], [], 0.25
        )

        if ready and capture.stdout.readline():
            last_ping = time.monotonic()

            if not led_on:
                GPIO.output(LED, GPIO.HIGH)
                led_on = True
                print("Ping detected: LED on", flush=True)

        if led_on and time.monotonic() - last_ping >= OFF_DELAY:
            GPIO.output(LED, GPIO.LOW)
            led_on = False
            print("Pinging stopped: LED off", flush=True)

except KeyboardInterrupt:
    pass

finally:
    capture.terminate()
    GPIO.output(LED, GPIO.LOW)
    GPIO.cleanup()
