PoodLL Database Activity Field
========================================
Thanks for downloading PoodLL.

Installation instructions and a video can be found at http://www.poodll.com .

If you are installing this mod separately from the full PoodLL kaboodle,
there should be only one folder "poodll" expanded after you unzip the zip file.
Place this folder into your moodle installation under the [site_root]/mod/data/field folder.

At the time of writing, Moodle doesn't completely handle language strings for 3rd party database fields well. 
So you should add these strings to the bottom of [site_root]/mod/data/lang/en/data.php
$string['poodll'] = 'PoodLL';
$string['namepoodll'] = 'PoodLL';

If you skip this step however, the sky won't fall in. 
It will just look a bit odd when Moodle tries to display the name of the database field.

After you placed the PoodLL files in the correct location, 
Then login to your site as an administrator and go to your Moodle site's "notifications" page.
Moodle should then guide you through the installation or upgrade of the PoodLL database activity field. 

When you make a database activity in Moodle, amongst the standard list of fields that you can choose from, 
you will see PoodLL Multimedia field. If you select this field you will have the option of choosing:
1) Video recording (via PoodLL's Red Server tokyo.poodll.com)
2) Audio Recording (via PoodLL's Red Server tokyo.poodll.com)
3) MP3 Recording (doesn't require Red5)
4) Whiteboard (draw pictures)
5) Snapshot (take photos with webcam)


*Please be aware that the PoodLL database activity field relies on the PoodLL Filter being installed, and won't work properly otherwise*

Good luck.

Justin Hunt
Chief PoodLL'er
http://www.poodll.com
poodllsupport@gmail.com