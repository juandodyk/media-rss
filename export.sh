FILES=
for FILE in `ls`; do
	if [ "${FILE##*.}" = "php" ] && ! diff $FILE ./php_cache/$FILE > /dev/null; then
		FILES="$FILES $FILE"
		cp $FILE ./php_cache
	fi
done
echo $FILES

ftp -in pterosaurio.xp3.biz <<SCRIPT
user "pterosaurio.xp3.biz" "pterosaurio1"
cd media
mput $FILES
quit
SCRIPT
