<?php
include('../../../../config.php');
include('../../../../filter/poodll/poodllinit.php');
?>


//<![CDATA[

		var currentDivContents = '';
		var currentPlayerID    = '';

		//variables from php		
		var audioplayerpart = "/filter/poodll/flash/poodllaudioplayer.lzx.swf9.swf";	
		var audioplayerLoc =  '<?php echo $CFG->httpswwwroot ?>' +  audioplayerpart;
		var videoplayerpart = "/filter/poodll/flash/poodllvideoplayer.lzx.swf9.swf";	
		var videoplayerLoc =  '<?php echo $CFG->httpswwwroot ?>' +  videoplayerpart;

		var flvserver = '<?php echo $CFG->poodll_media_server ?>';

		
		
		
		
		
      function loadAudioPlayer(rtmp_file, playerid, sampleid, width, height) {
	  
	  
			// if a player is already loaded, restore it's div contents
			if (currentDivContents != ''){
				rDc = document.getElementById(currentPlayerID);
			    rDc.innerHTML = currentDivContents;
			}
			// save current div contents
			currentDivContents = document.getElementById(playerid).innerHTML;

			// save current player id
			currentPlayerID = playerid;
			
			var playertype='rtmp';
			if(rtmp_file.substring(0,4)=='http'){
				playertype='http';
			}


/*
			   lzOptions = { ServerRoot: ''};
				lz.embed.swf({url: audioplayerLoc + '?red5url=' + flvserver +
					'&playertype=rtmp&mediapath='+ rtmp_file + 
					'&lzproxied=false', bgcolor: '#ffffff', width: '300', height: '40', id: playerid , accessible: 'false'});			
*/

				var so = new SWFObject(audioplayerLoc + '?red5url=' + flvserver +
					'&playertype='+ playertype +'&autoplay=true&mediapath='+ rtmp_file + 
					'&lzproxied=false', sampleid, width, height, '9');
							so.addParam('allowscriptaccess', 'always');
							so.addVariable('file',   rtmp_file);							
							
							
							//alert(audioplayerLoc + '?red5url=' + flvserver +'&playertype=rtmp&mediapath='+ rtmp_file + '&lzproxied=false');
							so.write(playerid);
						//	alert('playerid :'+ playerid);

      }
	  
	  
	        function loadVideoPlayer(rtmp_file, playerid, sampleid, width, height) {
	  
	  
			// if a player is already loaded, restore it's div contents
			if (currentDivContents != ''){
				rDc = document.getElementById(currentPlayerID);
			    rDc.innerHTML = currentDivContents;
			}
			// save current div contents
			currentDivContents = document.getElementById(playerid).innerHTML;

			// save current player id
			currentPlayerID = playerid;


/*
			   lzOptions = { ServerRoot: ''};
				lz.embed.swf({url: videoplayerLoc + '?red5url=' + flvserver +
					'&playertype=rtmp&mediapath='+ rtmp_file + 
					'&lzproxied=false', bgcolor: '#ffffff', width: '300', height: '40', id: playerid , accessible: 'false'});			
*/

				var so = new SWFObject(videoplayerLoc + '?red5url=' + flvserver +
					'&playertype=rtmp&mediapath='+ rtmp_file + 
					'&lzproxied=false', sampleid, width, height, '7');
							so.addParam('allowscriptaccess', 'always');
							so.addVariable('file',            rtmp_file);
							so.write(playerid);

      }

	  
	  
//]]>


