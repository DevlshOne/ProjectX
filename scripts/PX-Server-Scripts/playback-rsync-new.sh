#!/bin/bash

# Reordered NEW_PXS so cold9 is first to expidite testing -william Jan 26, 2018
#OLD_PXS=(taps1px1)
NEW_PXS=(cold9 cold1 cold2 cold3 cold4 cold5 cold6 cold7 verifier taps1)

## Added local copy to simplify syncing scripts
## William Mar 23, 2017
rsync -avz --delete /playback/ /playback-copy/

#for px in ${OLD_PXS[*]}
#    do
#
#    echo -e "\nRSYNC to $px..."
#    rsync -avz --delete -e ssh /playback/ root@$px.courtesycall.com:/playback/
#    ssh root@$px.courtesycall.com 'rsync -avz --delete -e ssh /playback/ /playback-copy/'
#
#    done

for px in ${NEW_PXS[*]}
    do

    echo -e "\nRSYNC to ${px}px1..."
    rsync -avz --delete -e ssh /playback/ root@${px}px1.courtesycall.com:/playback/
    ssh root@${px}px1.courtesycall.com 'rsync -avz --delete /playback/ /playback-copy/'

    echo -e "\nRSYNC to ${px}px2..."
    rsync -avz --delete -e ssh /playback/ root@${px}px2.courtesycall.com:/playback/
    ssh root@${px}px2.courtesycall.com 'rsync -avz --delete /playback/ /playback-copy/'

    done

echo -e "\nRSYNC to Keeper..."
rsync -avz --delete -e "ssh -i /root/.ssh/rsync_id" /playback/ root@keeper:/home/backups/projectX-001/playback/

