import paho.mqtt.client as mqtt
import mysql.connector

# MQTT settings
BROKER_URL = "broker.hivemq.com"
BROKER_PORT = 8000
TOPIC = "testtopic/temp/outTopic/esp8266"

# Hostinger database settings
DB_HOST = "srv630.hstgr.io"
DB_USER = "u153404388_db_DuyPham"
DB_PASSWORD = "9D@fGFo9"
DB_NAME = "u153404388_DuyPham"
DB_PORT = 3306

def save_to_database(sensor_value):
    connection = None
    cursor = None

    try:
        connection = mysql.connector.connect(host=DB_HOST, port=DB_PORT, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
        cursor = connection.cursor()
        query = """
            INSERT INTO pot_sensor_value (value)
            VALUES (%s)
        """
        cursor.execute(query, (sensor_value,))
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

        print("Subscribed to topic:", TOPIC)
    else:
        print("MQTT connection failed. Return code:", rc)


def on_message(client, userdata, msg):
    text_value = msg.payload.decode().strip()

    print("Received:", text_value)
    print("Topic:", msg.topic)

    try:
        sensor_value = float(text_value)
        save_to_database(sensor_value)

    except ValueError:
        print("Ignored non-numeric message:", text_value)


# Create a WebSocket MQTT client
client = mqtt.Client(transport="websockets")

client.on_connect = on_connect
client.on_message = on_message

print("Connecting to broker...")
client.connect(BROKER_URL, BROKER_PORT, 60)

client.loop_forever()

