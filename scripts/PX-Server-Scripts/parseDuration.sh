 #!/bin/sh




	echo -n `sox "$1" -n stat 2>&1 |grep Length |tr -d ' ' |cut -d ':' -f 2`

	echo " $1"


