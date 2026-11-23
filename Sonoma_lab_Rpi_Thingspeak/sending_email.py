import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

# Email account credentials
GMAIL_USER = 'sdsada@gmail.com'
GMAIL_PASS = 'hufhmoygdpuimowc'
# Recipient email (sending to yourself in this case)
recipient_email = 'sadsdsd@gmail.com'


# Email content
subject = 'Hello World!'
body = 'This is a test message saying Hello, World!'

# Setting up the message
msg = MIMEMultipart()
msg['From'] = GMAIL_USER
msg['To'] = recipient_email
msg['Subject'] = subject

# Attach the message body
msg.attach(MIMEText(body, 'plain'))

# Send the email
try:
   with smtplib.SMTP('smtp.gmail.com', 587) as smtpserver:
       smtpserver.ehlo()
       smtpserver.starttls()  # Secure the connection
       smtpserver.ehlo()
       smtpserver.login(GMAIL_USER, GMAIL_PASS)  # Log in to your Gmail account
       smtpserver.sendmail(GMAIL_USER, recipient_email, msg.as_string())  # Send the email
       print("Email sent successfully!")
except Exception as e:
   print(f"Failed to send email: {e}")
