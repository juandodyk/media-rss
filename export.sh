FILES=
for FILE in `ls`; do
	if [ "${FILE##*.}" = "php" ]; then
		FILES="$FILES $FILE"
	fi
done
echo $FILES

ftp -in pterosaurio.xp3.biz <<SCRIPT
user "pterosaurio.xp3.biz" "pterosaurio1"
cd media
mput $FILES
quit
SCRIPT
