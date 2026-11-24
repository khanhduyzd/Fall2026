#!/usr/bin/env python3

import os
import re
import time
import subprocess
import smtplib
from email.message import EmailMessage

# Raspberry Pi temperature limit
TEMP_LIMIT = 40.0

# Check temperature every 60 seconds
CHECK_INTERVAL = 60

SENDER_EMAIL = "yourgmail@gmail.com"
APP_PASSWORD = "your_new_app_password"
RECIPIENT_EMAIL = "recipient@gmail.com"


def get_cpu_temperature():
    """Return Raspberry Pi CPU temperature in Celsius."""

    try:
        # Raspberry Pi 3 normally supports this command
        result = subprocess.check_output(
            ["vcgencmd", "measure_temp"],
            text=True
        )

        # Example output: temp=45.2'C
        match = re.search(r"temp=([\d.]+)", result)

        if match:
            return float(match.group(1))

    except Exception:
        pass

    # Backup method
    with open("/sys/class/thermal/thermal_zone0/temp", "r") as file:
        millidegrees = int(file.read().strip())

    return millidegrees / 1000.0


def send_email(temperature):
    message = EmailMessage()
    message["From"] = SENDER_EMAIL
    message["To"] = RECIPIENT_EMAIL
    message["Subject"] = "Raspberry Pi High Temperature Warning"

    message.set_content(
        f"Warning: Raspberry Pi CPU temperature is "
        f"{temperature:.1f}°C, which is above the limit of {TEMP_LIMIT}°C."
    )

    # Gmail SMTP server using secure SSL
    with smtplib.SMTP_SSL("smtp.gmail.com", 465) as server:
        server.login(SENDER_EMAIL, APP_PASSWORD)
        server.send_message(message)

    print("Warning email sent.")


def main():
    alert_sent = False

    while True:
        temperature = get_cpu_temperature()
        print(f"Current CPU temperature: {temperature:.1f}°C")

        if temperature > TEMP_LIMIT and not alert_sent:
            send_email(temperature)
            alert_sent = True

        # Allow another alert after the temperature returns below the limit
        elif temperature <= TEMP_LIMIT:
            alert_sent = False

        time.sleep(CHECK_INTERVAL)


if __name__ == "__main__":
    main()
