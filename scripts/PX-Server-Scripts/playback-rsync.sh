#!/bin/sh


echo "\nRSYNC to PX2..."

rsync -avz --delete -e ssh /playback/* root@px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@px2.courtesycall.com:/playback-copy/ 

echo "\nRSYNC to PX3..."

rsync -avz --delete -e ssh /playback/* root@px3.courtesycall.com:/playback/ 
rsync -avz --delete -e ssh /playback-copy/* root@px3.courtesycall.com:/playback-copy/

echo "\nRSYNC to PX4..."

rsync -avz --delete -e ssh /playback/* root@px4.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@px4.courtesycall.com:/playback-copy/


echo "\nRSYNC to PX5..."

rsync -avz --delete -e ssh /playback/* root@px5.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@px5.courtesycall.com:/playback-copy/

echo "\nRSYNC to Verifier1PX2..."

rsync -avz --delete -e ssh /playback/* root@verifier1px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@verifier1px2.courtesycall.com:/playback-copy/

echo "\nRSYNC to PX6..."

rsync -avz --delete -e ssh /playback/* root@px6.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@px6.courtesycall.com:/playback-copy/



echo "\nRSYNC to TAPS1-PX1..."

rsync -avz --delete -e ssh /playback/* root@taps1-px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@taps1-px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD3-PX1..."

rsync -avz --delete -e ssh /playback/* root@cold3px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold3px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD3-PX2..."

rsync -avz --delete -e ssh /playback/* root@cold3px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold3px2.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD4-PX1..."
rsync -avz --delete -e ssh /playback/* root@cold4px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold4px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD4-PX2..."
rsync -avz --delete -e ssh /playback/* root@cold4px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold4px2.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD5-PX1..."
rsync -avz --delete -e ssh /playback/* root@cold5px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold5px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD5-PX2..."
rsync -avz --delete -e ssh /playback/* root@cold5px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold5px2.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD6-PX1..."
rsync -avz --delete -e ssh /playback/* root@cold6px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold6px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD6-PX2..."
rsync -avz --delete -e ssh /playback/* root@cold6px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold6px2.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD1-PX1..."
rsync -avz --delete -e ssh /playback/* root@cold1px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold1px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD1-PX2..."
rsync -avz --delete -e ssh /playback/* root@cold1px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold1px2.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD7-PX1..."
rsync -avz --delete -e ssh /playback/* root@cold7px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold7px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD7-PX2..."
rsync -avz --delete -e ssh /playback/* root@cold7px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold7px2.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD2-PX1..."
rsync -avz --delete -e ssh /playback/* root@cold2px1.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold2px1.courtesycall.com:/playback-copy/

echo "\nRSYNC to COLD2-PX2..."
rsync -avz --delete -e ssh /playback/* root@cold2px2.courtesycall.com:/playback/
rsync -avz --delete -e ssh /playback-copy/* root@cold2px2.courtesycall.com:/playback-copy/


echo "\nRSYNC to Keeper..."
rsync -avz --delete -e "ssh -i /root/.ssh/rsync_id" /playback root@keeper:/home/backups/projectX-001/


#echo "\nRSYNC to PX-dev..."

#rsync -avz --delete -e ssh /playback/* root@10.10.0.62:/playback/


#rsync -avz -e ssh /playback/* root@10.10.0.64:/playback/ 
#rsync -avz -e ssh /playback-copy/* root@10.10.0.64:/playback-copy/
