import paho.mqtt.client as mqtt
import mysql.connector

# MQTT broker and topic
BROKER_URL = "broker.hivemq.com"
BROKER_PORT = 8000  # WebSocket port

TOPIC = "testtopic/temp/outTopic/esp8266"

# Hostinger database information
DB_HOST = "YOUR_HOSTINGER_DATABASE_HOST"
DB_USER = "YOUR_DATABASE_USERNAME"
DB_PASSWORD = "YOUR_DATABASE_PASSWORD"
DB_NAME = "YOUR_DATABASE_NAME"


def save_to_database(sensor_value):
    connection = None
    cursor = None

    try:
        connection = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME
        )

        cursor = connection.cursor()

        sql = """
            INSERT INTO sensor_value (value)
            VALUES (%s)
        """

        cursor.execute(sql, (sensor_value,))
        connection.commit()

        print("Saved to database:", sensor_value)

    except mysql.connector.Error as error:
        print("Database error:", error)

    finally:
        if cursor is not None:
            cursor.close()

        if connection is not None and connection.is_connected():
            connection.close()


def on_connect(client, userdata, flags, rc):
    if rc == 0:
        print("Connected to HiveMQ broker!")

        client.subscribe(TOPIC)

        print(f"Subscribed to topic: {TOPIC}")

    else:
        print(f"Failed to connect, return code {rc}")


def on_message(client, userdata, msg):
    received_message = msg.payload.decode().strip()

    print(
        f"Received message: {received_message} "
        f"from topic: {msg.topic}"
    )

    try:
        sensor_value = float(received_message)

        save_to_database(sensor_value)

    except ValueError:
        print("Ignored non-numeric message:", received_message)


# Create MQTT client using WebSockets
client = mqtt.Client(transport="websockets")

# Attach callback functions
client.on_connect = on_connect
client.on_message = on_message

# Connect to HiveMQ
print("Connecting to broker...")
client.connect(BROKER_URL, BROKER_PORT, 60)

# Keep receiving messages
client.loop_forever()
