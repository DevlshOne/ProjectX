 #!/bin/sh



for i in *.wav ; do

	echo -n `sox "$i" -n stat 2>&1 |grep Length |tr -d ' ' |cut -d ':' -f 2`

	echo " $i"

done

