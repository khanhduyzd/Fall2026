#!/usr/bin/env python3

# ============================================================
# Title: Assignment 6 Program Launcher
# ============================================================
# Program Detail:
# Purpose: Allow the user to select and run PE0 through PE4.
# Inputs: User menu selection.
# Outputs: Executes the selected Python program.
# Date: October 8, 2026
# Compiler: Python 3 interpreter on Raspberry Pi OS
# Author: Duy Pham
# Version:
#   V1.0 - Initial assignment program launcher
# ============================================================
# File Dependencies:
#   os
#   pe0_sum.py
#   pe1_button.py
#   pe2_ping.py
#   pe3_ping_LED.py
#   pe4_wifi.py
# ============================================================

import os

while True:
    print("\nSelect the program you would like to execute:")
    print("---------------------------------------------")
    print("1) PE0: Sum and Even/Odd Calculator")
    print("2) PE1: Button-Press Detector")
    print("3) PE2: Button-Controlled Ping")
    print("4) PE3: Incoming Ping LED")
    print("5) PE4: Wi-Fi Controller")
    print("6) Exit")

    choice = input("Enter your choice: ")

    if choice == "1":
        os.system("python3 pe0_sum.py")
    elif choice == "2":
        os.system("python3 pe1_button.py")
    elif choice == "3":
        os.system("python3 pe2_ping.py")
    elif choice == "4":
        os.system("python3 pe3_ping_LED.py")
    elif choice == "5":
        os.system("python3 pe4_wifi.py")
    elif choice == "6":
        print("Exiting program.")
        break
    else:
        print("Invalid choice.")
