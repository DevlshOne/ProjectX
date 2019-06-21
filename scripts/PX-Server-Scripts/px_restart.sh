#!/bin/sh
echo "Stopping PX..."
/etc/init.d/ProjectX-Server stop;
echo "Killing rouge linphones..."
killall -9 lt-linphonec;
echo "Should say 'lt-linphonec: no process found' below..."
killall -9 lt-linphonec;
echo "Starting PX back up..."
/etc/init.d/ProjectX-Server start
echo "Done."



