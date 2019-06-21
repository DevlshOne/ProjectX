#!/bin/sh

for CPUFREQ in /sys/devices/system/cpu/cpu*/cpufreq/scaling_governor; do [ -f $CPUFREQ ] || continue; echo -n performance > $CPUFREQ; done


mount -t tmpfs -o size=5700M tmpfs /playback

cp -a /playback-copy/* /playback/
