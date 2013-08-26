#!/usr/bin/python

import smtplib

sender = 'hbops@hbops.localdomain'
receivers = ['avery.scott@rackspace.com']

message = """From: hbops <hbops@hbops.localdomain>
To: Avery <avery.scott@rackspace.com>
Subject: SMTP e-mail test

This is a test e-mail message.
"""

# https://forums.rackspace.corp/discussion/125/smtp-mail-server-value-for-internal-jenkins

#try:
if 1:
   smtpObj = smtplib.SMTP('10.7.9.75')
   smtpObj.sendmail(sender, receivers, message)         
   print "Successfully sent email"
#except smtplib.SMTPException:
#    print smtplib.SMTPException

#except smtplib.SMTPException:
#   print "Error: unable to send email"
