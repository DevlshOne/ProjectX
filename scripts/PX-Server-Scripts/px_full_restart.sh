#!/bin/sh

echo "Stopping PX..."
/etc/init.d/ProjectX-Server stop;

echo "Killing rouge linphones..."
killall -9 lt-linphonec;

echo "Should say 'lt-linphonec: no process found' below..."
killall -9 lt-linphonec;

echo "Cleaning asterisk outgoing folder..."
rm /var/spool/asterisk/outgoing/calling-*

echo "Restarting asterisk..."
service asterisk restart;

echo "Starting PX back up..."
/etc/init.d/ProjectX-Server start

echo "Done."
