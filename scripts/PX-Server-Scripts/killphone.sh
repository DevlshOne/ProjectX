#!/bin/sh

#
# Kill linphones matching a pattern.
#  usage: killphone.sh <extension>
#
# By Daniel Roberson (droberson@courtesycall.com)
#

if [ ! $1 ]; then
    echo "usage: $0 <extension> [-y]"
    echo "\t-y - do not prompt to kill processes"

    exit 1
fi

# Pull matching PIDs
PIDS=`ps ax | grep linphonec | grep $1 | awk {'print $1'}`

# Display them PIDs
echo "PIDs to be killed:"
echo
ps -o pid,args -p $PIDS
echo

# Do not prompt, just kill.
if [ "$2" = "-y" ]; then
    kill -9 $PIDS
    echo "Killed."
    exit 0
fi

# Prompt to kill processes.
while true; do
    read -p "Kill these processes? [Y/N]: " yn
    case $yn in
	[Yy]*)
	    kill -9 $PIDS
	    echo "Killed."
	    exit 0
	    ;;
	[Nn]*)
	    echo "Not killing processes. Exiting."
	    exit 0
	    ;;
    esac
done


