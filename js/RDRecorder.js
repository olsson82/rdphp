
// Rivendell Web Interface
//
//   (C) Copyright 2019 Genesee Media Corporation <bmcglynn@geneseemedia>
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License version 2 as
//   published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public
//   License along with this program; if not, write to the Free Software
//   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.


//webkitURL is deprecated but nevertheless
URL = window.URL || window.webkitURL;

var gumStream; 	//stream from getUserMedia()
var recorder; 	//WebAudioRecorder object
var input; 	//MediaStreamAudioSourceNode  we'll be recording
var encodingType = "wav";  // Can be wav, mp3, or ogg
var encodeAfterRecord = true;  // when to encode
// Where all of the Javascript files are
var jsWorkerDir = "../js/"; // must end with slash

//Globalize variables
var recordingBlob; 
var vtNode; 
var recordNode; 
var playNode;
var statusNode;
var recordButton;
var stopButton;
var saveButton;
var cart;
var line; //Log Line number
var rdGroup;
var recordingEnabled = false;

// shim for AudioContext when it's not avb. 
var AudioContext = window.AudioContext || window.webkitAudioContext;
var audioContext; //new audio context to help us record


function startRecording() {
	console.log("startRecording() called");

	/*
		Simple constraints object, for more advanced features see
		https://addpipe.com/blog/audio-constraints-getusermedia/
	*/
    
    var constraints = { audio: true, video:false }

    /*
    	We're using the standard promise based getUserMedia() 
    	https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia
	*/

        //BPM - This will not work unless the connection is HTTPS
	navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
		__log("getUserMedia() success, stream created, initializing WebAudioRecorder...");

		/*
			create an audio context after getUserMedia is called
			sampleRate might change after getUserMedia is called, like it does on macOS when recording through AirPods
			the sampleRate defaults to the one set in your OS for your playback device

		*/
		audioContext = new AudioContext();

		//assign to gumStream for later use
		gumStream = stream;
		
		/* use the stream */
		input = audioContext.createMediaStreamSource(stream);
		
		//stop the input from playing back through the speakers
		//input.connect(audioContext.destination)

		recorder = new WebAudioRecorder(input, {
		  workerDir: jsWorkerDir, 
		  encoding: encodingType,
		  numChannels:2, //2 is the default, mp3 encoding supports only 2
		  onEncoderLoading: function(recorder, encoding) {
		    // show "loading encoder..." display
		    __log("Loading "+encoding+" encoder...");
		  },
		  onEncoderLoaded: function(recorder, encoding) {
		    // hide "loading encoder..." display
		    __log(encoding+" encoder loaded");
		  }
		});

		//Process the recording after complete
		recorder.onComplete = function(recorder, blob) { 
			__log("Encoding complete");
			recordingBlob = blob;
			createAudioPlayer(blob,recorder.encoding);
		}

		recorder.setOptions({
		  timeLimit:180,
		  encodeAfterRecord:encodeAfterRecord,
	          ogg: {quality: 0.5},
	          mp3: {bitRate: 160}
	    });

		//start the recording process
		recorder.startRecording();

		 __log("Recording started");

	}).catch(function(err) {
	  	//enable the record button if getUSerMedia() fails
    	        recordButton.disabled = false;
    	        stopButton.disabled = true;

	});

	//disable the record button
    	recordButton.disabled = true;
    	stopButton.disabled = false;
    	saveButton.disabled = true;
    	cancelButton.disabled = true;
}

function stopRecording() {
	console.log("stopRecording() called");
	
	//stop microphone access
	gumStream.getAudioTracks()[0].stop();

	//disable the stop button
	stopButton.disabled = true;
	recordButton.disabled = false;
	recordButton.innerHTML = "Redo";
	saveButton.disabled = false;
	cancelButton.disabled = false;
	
	//tell the recorder to finish the recording (stop recording + encode the recorded audio)
	recorder.finishRecording();

	__log('Recording stopped');
}

//Saves the recording to the server
function saveRecording() {

	console.log("saveRecording() called");

        //Upload into Rivenvell
        //TODO - dynamically set group from the AddControls function
        importAudioToCart(recordingBlob,line,rdGroup);

        //Disable button once upload is complete
	saveButton.disabled = true;
	cancelButton.disabled = true;

	//TODO - Set status indicator upon successful import
	__log('Recording saved');
}

//Cancels the recording process
function cancelRecording() {

        //Line and recordNode are all set in the Add-controls function
	console.log("cancelRecording() called");
        //Reset the buttons and clear content
        vtNode.innerHTML = "<td><div id=\"vc-" + line + " \"><button onclick=\"addRecordingControls(" +  line + ",'" + rdGroup + "'," + cart + ")\">Insert Voicetrack</button></div></td>";
	recordNode.innerHTML = "<p></p>";
	playNode.innerHTML = "<p></p>";

        recordingEnabled = false;
}


// Gets the filename from the form and uploads it
function importFileToCart(fileNode,line,group) {


        var file = fileNode.files[0];

    	//if (!files.length) {
      	//	alert('Please select a file!');
      	//	return;
    	//}

 	// Blackout Recording Controls
      	recordNode.innerHTML = "<p></p>";

	// Blackout submit button
	document.getElementById("uploadButton").disabled = true;

 	//file is a subtype of Blob, so we can use the regular function
        importAudioToCart(file,line,group);


}

// Function to upload file to server and import into Rivendell
// Looks in the temporary location used previously for upload
function importAudioToCart(blob,rdLine,rdGroup) {

	var xhr=new XMLHttpRequest();
	xhr.onload=function(e) {
  		if(this.readyState === 4) {
      			console.log("Server returned: ",e.target.responseText);

        		//Clear out the controls
        		//Note - By putting the controls here, the system will wait 
        		//until a status comes back before disabling the controls. 
        		//To not wait, this could be put right after the xhr.send(fd)
        		//and then the controls will be disabled.

        		recordNode.innerHTML = "<p>Voicetrack Saved</p>";
        		playNode.innerHTML = "<p></p>";
        		statusNode.innerHTML = "<p>OK</p>";
                	recordingEnabled = false;
  		}
	};
	var fd=new FormData();
	fd.append("audio_data",blob, rdLine);
	fd.append("LINE",rdLine);
	fd.append("CART",cart);
	fd.append("GROUP",rdGroup);
	fd.append("LOGNAME",logName);
	fd.append("USERNAME",userName);
	xhr.open("POST","../includes/cart_import.php",true);//Import Cart
	xhr.send(fd);

}


// Inserts an Audioplayer to listen to recording
function createAudioPlayer(blob,encoding) {
	
	var url = URL.createObjectURL(blob);
	var au = document.createElement('audio');

	//add controls to the <audio> element
	au.controls = true;
	au.src = url;

        //Clear out previous instances of the player
        if(playNode.hasChildNodes()) {

            while (playNode.firstChild) {
               playNode.removeChild(playNode.firstChild);
            }
        }

        playNode.appendChild(au);
}


//This will be called inline when recording shall be made
//for a voicetrack
function addRecordingControls(lineNumber, groupName, cartNo) {

        //The user can press the Insert Voicetrack for another voicetrack
        //on the page and cause a lot of problems.  
        //Use a global Javascript variable that gets set
        //when controls are added and then removed when saved or cancelled
        if(recordingEnabled) {
	   var checkNode = document.getElementById("pl-" + lineNumber);

           //Set warning message in Player area
	   checkNode.innerHTML = "<p>Another recording is in progress.  Please complete or cancel</p>";
           return;
        }
        else {
           recordingEnabled = true;
        }

	recordNode = document.getElementById("vc-" + lineNumber);
	playNode = document.getElementById("pl-" + lineNumber);
	statusNode = document.getElementById("st-" + lineNumber);
	vtNode = document.getElementById("vt-" + lineNumber);
        cart = cartNo; //Will be 0 if this is a new cart
	line = lineNumber;
	rdGroup = groupName;
	console.log("addRecordingControls() called for node: " + lineNumber);
	recordNode.innerHTML = "<p>Voicetrack Controls:</p>";

        //TODO - change into indicators based on what the person is doing
  	vtNode.innerHTML = "<p>Recording</p>";

        addUploadControls(line,playNode);
   
	//Add the stop/start/record buttons
	recordButton = document.createElement('button');
        recordButton.innerHTML = "Start";
        recordButton.disabled = false;

	stopButton = document.createElement('button');
        stopButton.innerHTML = "Stop";
        stopButton.disabled = true;

	saveButton = document.createElement('button');
        saveButton.innerHTML = "Save";
        saveButton.disabled = true;

	cancelButton = document.createElement('button');
        cancelButton.innerHTML = "Cancel";
        cancelButton.disabled = false;

        recordNode.appendChild(recordButton); 
        recordNode.appendChild(stopButton); 
        recordNode.appendChild(saveButton); 
        recordNode.appendChild(cancelButton); 

	recordButton.addEventListener("click", startRecording);
	stopButton.addEventListener("click", stopRecording);
	saveButton.addEventListener("click", saveRecording);
	cancelButton.addEventListener("click", cancelRecording);

}

//Adds upload controls to upload tracks vs. record them
function addUploadControls(lineNumber,uploadNode) {

  
        //Add File Selector
        var inHTML      = '<p>Upload File</p>\n';
        var uploadID    = 'ul' + lineNumber;
	var uploadGRP   = "'" + rdGroup + "'";

	inHTML = inHTML + '<input type="file" id="' + uploadID + '" name="audiofile">\n';
        inHTML = inHTML + '<button id="uploadButton"';
        inHTML = inHTML + 'onclick="importFileToCart(' + uploadID + ',';
	inHTML = inHTML + lineNumber + ',' + uploadGRP + ')">\n';
        inHTML = inHTML + 'Upload</button>';

	uploadNode.innerHTML = inHTML;
        console.log('Upload Node:\n' + inHTML);
	
}

//helper function
function __log(e, data) {

        //TODO - Send to Console
	//log.innerHTML += "\n" + e + " " + (data || '');
	console.log(e + "" + (data || ''));

}
