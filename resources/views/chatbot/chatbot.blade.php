<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Speech to Text with Silence Detection</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .mic-btn {
            font-size: 24px;
            cursor: pointer;
            
        }
        .mic-btn.green { color: green; }
        .mic-btn.red { color: red; }

        .container{
  max-width:1170px; 
  margin:auto;
}
img{
  max-width:100%;
}
.incoming_msg_img {
  display: inline-block;
  width: 6%;
}
.received_msg {
  display: inline-block;
  padding: 0 0 0 10px;
  vertical-align: top;
  width: 92%;
 }
 .received_withd_msg p {
  background: #ebebeb none repeat scroll 0 0;
  border-radius: 3px;
  color: #646464;
  font-size: 14px;
  margin: 0;
  padding: 5px 10px 5px 12px;
  width: 100%;
}
.time_date {
  color: #747474;
  display: block;
  font-size: 12px;
  margin: 8px 0 0;
}
.received_withd_msg { width: 57%;}
.mesgs {
  float: left;
  padding: 30px 15px 0 25px;
  width: 98%;
}

 .sent_msg p {
  background: #05728f none repeat scroll 0 0;
  border-radius: 3px;
  font-size: 14px;
  margin: 0; color:#fff;
  padding: 5px 10px 5px 12px;
  width:100%;
}
.outgoing_msg{ overflow:hidden; margin:26px 0 26px;}
.sent_msg {
  float: right;
  width: 46%;
}
.input_msg_write input {
  background: rgba(0, 0, 0, 0) none repeat scroll 0 0;
  border: medium none;
  color: #4c4c4c;
  font-size: 15px;
  min-height: 48px;
  width: 100%;
}

.type_msg {
  border-top: 1px solid #c4c4c4;
  position: relative; 
  margin-top: 20px;
}

.msg_send_btn {
  background: #05728f none repeat scroll 0 0;
  border: medium none;
  border-radius: 50%;
  color: #fff;
  cursor: pointer;
  font-size: 17px;
  height: 33px;
  position: absolute;
  right: 0;
  top: 11px;
  width: 33px;
}
.messaging { 
  padding: 0 0 50px 0; 
}
.msg_history {
  height: 400px;
  overflow-y: auto;
}

    </style>
</head>
<body>
	<div class="container">
        <h3 class=" text-center">Messaging with ChatBot</h3>
        <div class="messaging">
                <div class="inbox_msg">

                <div class="mesgs">
                    <div id="msg_history" class="msg_history">
                    <div class="incoming_msg">
                        <div class="incoming_msg_img"> <img src="https://raw.githubusercontent.com/strahlistvan/Chatbot/master/robot-chef-cap-pizza-red-2.jpg" alt="sunil"> </div>
                            <div class="received_msg">
                              <div class="received_withd_msg">
                                <p>How Can i Help You</p>
                                <span class="time_date" id="#welcome_time_date"></span>
                                </div>

                            </div>

                            
                        </div>

                          <!-- Typing indicator (hidden by default) -->
       
                        

                    </div>
                   

                    <div class="type_msg text-center">
                        <div id="micButton" class="mic-btn pt-2 green"><i class="fas fa-microphone fa-2x"></i></div>
                    </div>
                </div>
                
            </div>
        </div>
       
        </div>

{{-- -------------------------For chat bot------------------------ --}}
<script>
    $(document).ready(function() {
        function stopAudio() {
if (globalAudioElement) {
globalAudioElement.pause(); // Pause the audio
globalAudioElement.currentTime = 0; // Reset the audio to the beginning
}
}
    var globalAudioElement;
    /********** chatbot.js **********/


function ChatBot(accessToken, responseHandlerFunction) {

this.accessToken = accessToken;
this.baseUrl = "https://api.api.ai/v1/";
this.responseHandlerFunction = responseHandlerFunction;

this.send = function(messageText, handler = this.responseHandlerFunction) {
    stopAudio();
    function setResponse(val) {
        stopAudio();
        var responseText   = val;
        //custom function parameter to handle response
        handler(responseText);
    };


     // for chatgpt response
//              $.ajax({
//         url: "{{ url('/chatd') }}",
//             type: "POST",
//             data: { 
//                 message: messageText,
//                 _token: '{{csrf_token()}}'
//             },
//             dataType: 'JSON',
//     success: function(response) {
//         // console.log(response);
//         // Trim the response text before setting it in the textarea
//          // Hide the loading overlay on successful response

//         //  $("#loadingOverlay").hide();
//         var trimmedResponse = response.trim();
//         // $("#myTextarea").val(trimmedResponse);
//         response(trimmedResponse);
//     },

// });

    $.ajax({
        type: "POST",
        url: "{{ url('/ct') }}",
        headers: {
'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
},
        contentType: "application/json; charset=utf-8",
        data: JSON.stringify({ 
message: messageText,
_token: $('meta[name="csrf-token"]').attr('content') // Assuming you've set up the meta tag
}),
    dataType: 'JSON',

        success: function(data) {
            var trimmedResponse = data.data.trim();
            setResponse(trimmedResponse);
            globalAudioElement = new Audio(data.audioPath);
    globalAudioElement.autoplay = true;
    globalAudioElement.controls = false;
    globalAudioElement.style.display = "none"; 

    // Play the audio
    globalAudioElement.play()
        },
        error: function() {
            // setResponse("Internal Server Error");
            $('#typingIndicator').remove();
            console.log('Internal Server Error');

        }
    });
};

}
    
/********  chat_control.js *******/




      




// {{-- -------------------------For speech to text------------------------ --}}

    
        let audioContext;
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;
        let audioInput, analyser, silenceDetectionNode;
        let silenceThreshold = -20; // Silence threshold in dB
        let isSpeechDetected = false;
        let silenceTimeoutId;
        let silenceDelay = 3000; // Delay in milliseconds to wait before stopping the recording after silence is detected
               // Function to stop the audio
function stopAudio() {
    if (globalAudioElement) {
        globalAudioElement.pause(); // Pause the audio
        globalAudioElement.currentTime = 0; // Reset the audio to the beginning
    }
}

        $('#micButton').click(async function() {
            if (!isRecording) {
                stopAudio();
                await startRecording();
            } else {
                stopAudio();
                stopRecordingManually();
            }
        });
    
        // async function startRecording() {
        //     if (!audioContext) {
        //         audioContext = new (window.AudioContext || window.webkitAudioContext)();
        //         await audioContext.audioWorklet.addModule("{{asset('public/assets')}}/audio-processor.js"); // Path to your audio processor worklet
        //     }
    
        //     navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        //         mediaRecorder = new MediaRecorder(stream);
        //         audioInput = audioContext.createMediaStreamSource(stream);
        //         analyser = audioContext.createAnalyser();
        //         silenceDetectionNode = new AudioWorkletNode(audioContext, 'silence-detection-processor', {
        //             parameterData: { silenceThreshold: silenceThreshold }
        //         });
    
        //         audioInput.connect(analyser);
        //         analyser.connect(silenceDetectionNode);
        //         silenceDetectionNode.connect(audioContext.destination);
    
        //         silenceDetectionNode.port.onmessage = (event) => {
        //             processAudio(event.data.averageDb);
        //         };
    
        //         prepareRecording();
        //     });
        // }

        async function startRecording() {
            // stopAudio()
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        await audioContext.audioWorklet.addModule("{{asset('public/assets')}}/audio-processor.js"); // Path to your audio processor worklet
    }

  
    
    // Enable noise suppression in the getUserMedia call
    navigator.mediaDevices.getUserMedia({ 
        audio: { noiseSuppression: true } // Modify this line to include noiseSuppression
    }).then(stream => {
        // stopAudio(); // Make sure this function is defined and accessible
        console.log("stream");
        mediaRecorder = new MediaRecorder(stream);
        audioInput = audioContext.createMediaStreamSource(stream);
        analyser = audioContext.createAnalyser();
        silenceDetectionNode = new AudioWorkletNode(audioContext, 'silence-detection-processor', {
            parameterData: { silenceThreshold: silenceThreshold }
        });

        audioInput.connect(analyser);
        analyser.connect(silenceDetectionNode);
        silenceDetectionNode.connect(audioContext.destination);

        silenceDetectionNode.port.onmessage = (event) => {
            processAudio(event.data.averageDb);
        };

        prepareRecording();
    });
}

    
        function prepareRecording() {
            mediaRecorder.ondataavailable = (event) => {
                audioChunks.push(event.data);
            };
    
            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                sendAudioToServer(audioBlob);
                audioChunks = [];
            };
    
            $('#micButton').removeClass('green').addClass('red');
            $('#micButton i').removeClass('fa-microphone').addClass('fa-stop');
            isRecording = true;
        }
    
        function processAudio(averageDb) {
            if (!isRecording) return;
    
            if (averageDb > silenceThreshold && !isSpeechDetected) {
                isSpeechDetected = true;
                if (silenceTimeoutId) {
                    clearTimeout(silenceTimeoutId);
                    silenceTimeoutId = null;
                }
                if (mediaRecorder.state === "inactive") {
                    mediaRecorder.start();
                }
            } else if (averageDb <= silenceThreshold && isSpeechDetected) {
                if (!silenceTimeoutId) {
                    silenceTimeoutId = setTimeout(() => {
                        isSpeechDetected = false;
                        if (mediaRecorder.state === "recording") {
                            mediaRecorder.stop();
                        }
                        prepareRecording(); // Prepare for next recording
                        silenceTimeoutId = null;
                    }, silenceDelay);
                }
            }
        }
    
        function stopRecordingManually() {
            if (mediaRecorder && mediaRecorder.state === "recording") {
                mediaRecorder.stop();
            }
            isRecording = false;
            resetRecordingState();
            
        }
    
        function resetRecordingState() {
            $('#micButton').removeClass('red').addClass('green');
            $('#micButton i').removeClass('fa-stop').addClass('fa-microphone');
        }





        function response(usertext) {

var messageSendButton = $("#msg_send_btn");
var messageInput = usertext;

var dateFormat = function(date) {
var str = date.getHours() < 10 ? "0" : "";
str += date.getHours() + ":"; 
str += date.getMinutes() < 10 ? "0" : "";
str += date.getMinutes();
return str;
}

var scrollDown = function() {
var msgHistoryHeight = document.querySelector("#msg_history").scrollHeight;
console.log("scroll height: " + msgHistoryHeight);
document.querySelector("#msg_history").scrollTo(0,msgHistoryHeight);
}

var appendIncomingMessageDOM = function(messageText) {
   

var incomingMsgDiv = $("<div></div>").attr("class", "incoming_msg").appendTo("#msg_history");

var incomingMsgImgDiv = $("<div></div>").attr("class", "incoming_msg_img").appendTo(incomingMsgDiv);
$("<img></img>").attr({"src" : "https://raw.githubusercontent.com/strahlistvan/Chatbot/master/robot-chef-cap-pizza-red-2.jpg", "alt" : "PizzaBot"} )
                .appendTo(incomingMsgImgDiv);

var receivedMsgDiv = $("<div></div>").attr("class", "received_msg").appendTo(incomingMsgDiv);
var receivedWithMsgDiv = $("<div></div>").attr("class", "received_withd_msg").appendTo(receivedMsgDiv);
$("<p></p>").text(messageText).appendTo(receivedWithMsgDiv);
$("<span></span>").attr("class", "time_date")
                  .text("Today - " + dateFormat(new Date()))
                  .appendTo(receivedWithMsgDiv);
scrollDown();
messageText = "";
$('#typingIndicator').remove();

}

var appendOutgoingMessageDOM = function(messageText) {
    // $('#typingIndicator').show();

var outgoingMsgDiv = $("<div></div>").attr("class", "outgoing_msg").appendTo("#msg_history");
var sentMsgDiv = $("<div></div>").attr("class", "sent_msg").appendTo(outgoingMsgDiv);
$("<p></p>").text(messageText).appendTo(sentMsgDiv);
$("<span></span>").attr("class", "time_date")
                  .text("Today - " +dateFormat(new Date()))
                  .appendTo(sentMsgDiv);
scrollDown();                                 messageText = "";   


// for typing
$('#msg_history').append(`
            <div id="typingIndicator">
                <div class="incoming_msg_img"> <img src="https://raw.githubusercontent.com/strahlistvan/Chatbot/master/robot-chef-cap-pizza-red-2.jpg" alt="bot"> </div>
                <div class="received_msg">
                    <div class="received_withd_msg">
                        <p>Typing...</p>
                    </div>
                </div>
            </div>
        `);


}

var chatbot = new ChatBot("7f7477c649f044c8ae2ec2dc24936701", appendIncomingMessageDOM);


appendOutgoingMessageDOM(messageInput);
chatbot.send(messageInput);



if (event.keyCode == 13) {
    appendOutgoingMessageDOM(messageInput);
    chatbot.send(messageInput);
    
}


}



    
        function sendAudioToServer(audioBlob) {
            const formData = new FormData();
            formData.append('audio', audioBlob);
            // Adjust the URL to your server endpoint here
            formData.append('_token', '{{ csrf_token() }}'); // Adjust as needed for your CSRF token handling
            $.ajax({
                url: "{{ url('/transcribe') }}", // Update to your server endpoint
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    // $('#transcriptionResult').append(data.transcript);

                    if (data.transcript=='' || data.transcript=='Thank you for watching.'|| typeof data.transcript !== 'string' ) {
                        
                    } else {
                        response(data.transcript);
                        
                    }

                   

                   
                    
                },
                error: function(xhr, status, error) {
                    console.error("Error sending audio to server:", error);
                    // $('#transcriptionResult').val("Error processing audio. Please try again.");
                }
            });
        }
    });
    </script>
    
</body>
</html>
