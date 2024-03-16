<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Speech to Text with Silence Detection</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
     body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
        }
        .container {
            max-width: 100%;
            height: 100vh; /* Make container full viewport height */
            display: flex;
            align-items: center;
            padding: 20px; /* Adjust based on your layout */
            justify-content: center
        }
        .message-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 160px); /* Adjust based on total height of other elements */
            overflow-y: auto;
            width: 100%;
        }
        .user-message, .ai-message {
            margin: 5px;
            padding: 10px;
            border-radius: 20px;
            text-align: right;
        }
        .user-message {
            align-self: flex-end;
            background-color: #dcf8c6;
        }
        .ai-message {
            align-self: flex-start;
            background-color: #f1f0f0;
            text-align: left;
        }

        .mic-btn {
            font-size: 24px;
            cursor: pointer;
            
        }
        .mic-btn.green { color: green; }
        .mic-btn.red { color: red; }

    </style>
</head>
<body class=" bg-light">
   
    <div class="container">
        <div class="col-md-8 ">
            <div class="bg-white rounded-3 p-4 shadow" style="width: 100%;">
                 <!-- Assistant Info Header -->
            <div class="assistant-info text-center ">
                <h4> <b class="text-success">Assistant:</b> {{ $assistantName }}</h2>
                {{-- <p>ID: {{ $assistantId }}</p> --}}
            </div>
                <div id="chatOutput" class="message-container"></div>
                <div class="type_msg text-center">
                    <div id="micButton" class="mic-btn pt-2 green"><i class="fas fa-microphone fa-2x"></i></div>
                </div>
                {{-- <form id="chatForm" class="mt-4 d-flex gap-3">
                    <input required type="text" name="input" class="form-control" placeholder="Type your question here...">
                    <button type="submit" class="btn btn-primary">Send</button>
                </form> --}}
            </div>
        </div>
    </div>
{{-- -------------------------For chat bot------------------------ --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // let audioQueue = [];
    // let isPlaying = false;
    var globalAudioElement;
    let audioQueue = [];
    let currentAudio = null;
    const splitters = ["?", "!", ".", ";"];
    let accumulatedText = ""; // Holds accumulating text
    let timeoutId; // To manage timeout for sending accumulated text
    // let threadid = "{{ $threadid }}";
    let threadid = "{{ $threadid }}";
    let assistantId = "{{ $assistantId }}";
    //     audioQueue.forEach(audio => {
    //         audio.pause(); // Stops playing the audio
    //         audio.currentTime = 0; // Resets the audio play time
    //     });
    //     audioQueue = []; // Clears the queue
    //     isPlaying = false; // Resets the playing state
    // }

    function response(usertext) {
        event.preventDefault();
        const inputVal = usertext;
        // if (!inputVal) return;
     
        // Display User Message
        const userMessageHtml = `<div class="user-message"><strong>User:</strong> ${inputVal}</div>`;
        $("#chatOutput").append(userMessageHtml);
        
    
 // Reset environment for new input
 resetAudioEnvironment(); // Prepare environment for new response
        // Create AI message container for new response
        const aiMessageContainer = $("<div class='ai-message'><strong>AI:</strong> </div>");
        $("#chatOutput").append(aiMessageContainer);

        const encodedQuestion = encodeURIComponent(inputVal);
        const source = new EventSource(`{{url('/ask')}}?question=${encodedQuestion}&threadid=${threadid}&assistantId=${assistantId}`);
        let aiResponseBuffer = "";
        // processIncomingTexts(' ');
    //     let buffer = "";
    // const splitters = [".", ",", "?", "!", ";", ":", "—", "-", "(", ")", "[", "]", "}"];
    let accumulatedText = "";
        source.addEventListener("update",  function (event) {
            if (event.data === "<END_STREAMING_SSE>") {
                source.close();
                // processIncomingTexts(' ');
                flushAccumulatedText(); // Process any remaining accumulated text
            } else {
                // // Directly append received AI Response part
                // aiResponseText += event.data;
                // accumulatedText += event.data;

                aiMessageContainer.append(document.createTextNode(event.data));
                processIncomingText(event.data);
        
            }
        });

        //  // Listen for audio updates
        //  source.addEventListener("audioUpdate", function(event) {
        //     var data = JSON.parse(event.data);
        //     if(data.audioUri) {
        //         // Play the received audio URI
        //         var audio = new Audio(data.audioUri);
        //         audio.play().catch(e => console.error("Audio playback error", e));
        //     } else if(data.error) {
        //         console.error("Text-to-Speech Error:", data.error);
        //     }
        // });

        source.onerror = function() {
            // Handle error or close event
            source.close(); // Close the connection on error or completion
        };












   }


   function processIncomingText(text) {
    accumulatedText += text;
    clearTimeout(timeoutId); // Reset the timer
    timeoutId = setTimeout(flushAccumulatedText, 3000); // Set a timeout to flush text if no splitters are found

    let sentences = accumulatedText.split(/(?<=[?!.;])/);
    if (sentences.length > 1) {
        // Process all but the last sentence (which might be incomplete)
        sentences.slice(0, -1).forEach(sentence => {
            // Trim the sentence and add a space at the end for natural speech separation
            let processedSentence = sentence.trim() + " ";
            if (processedSentence.trim()) { // Check again in case it was just a space
                processIncomingTexts(processedSentence);
            }
        });
        accumulatedText = sentences[sentences.length - 1]; // Keep the last, potentially incomplete sentence
    }
}


    function flushAccumulatedText() {
        if (accumulatedText.trim()) {
            processIncomingTexts(accumulatedText.trim());
            accumulatedText = ""; // Clear the accumulated text
        }
    }

//    function enqueueTextForTTS(text) {
//         if (!text) return;
//         const ttsSrc = `{{url('/text-to-speech')}}?text=${encodeURIComponent(text)}`;
//         let audio = new Audio(ttsSrc);
//         audio.onended = () => {
//             playNextInQueue(); // Ensure the next item in the queue plays after one finishes.
//         };
//         if (!currentAudio || currentAudio.ended) {
//             currentAudio = audio;
//             audio.play().catch(e => console.error("Audio playback error", e));
//         } else {
//             audioQueue.push(audio);
//         }
//     }

//     function playNextInQueue() {
//         if (audioQueue.length > 0) {
//             currentAudio = audioQueue.shift();
//             currentAudio.play().catch(e => console.error("Audio playback error", e));
//         }
//     }

   

    // 

   function convertTextToSpeech(text) {
        if (text.trim() === "") return; // Skip empty text
        const ttsSrc = `{{url('/text-to-speech')}}?text=${encodeURIComponent(text)}`;

        // Stop the current audio if it's playing
        stopCurrentAudio();
        

        // Play new text-to-speech audio
        currentAudio = new Audio(ttsSrc);
        currentAudio.play().catch(e => console.error("Audio playback error", e));
    }

    function stopCurrentAudio() {
        if (currentAudio && !currentAudio.paused) {
            currentAudio.pause();
            currentAudio.currentTime = 0; // Reset the playback position
        }
    }


// websockets


function processIncomingTexts(text) {
       

    const voiceId = "BSxBufeT2PiJqGy8i3Ui"; // replace with your voice_id
const model = 'eleven_monolingual_v1';
const wsUrl = `wss://api.elevenlabs.io/v1/text-to-speech/${voiceId}/stream-input?model_id=${model}`;
const socket = new WebSocket(wsUrl);
let accumulatedAudioData = []; // Array to hold audio data chunks
// 2. Initialize the connection by sending the BOS message
socket.onopen = function (event) {
    const bosMessage = {
        "text": " ",
        "voice_settings": {
            "stability": 0.5,
            "similarity_boost": 0.8
        },
//         "generation_config": {
//     "chunk_length_schedule": [120, 160, 250, 290]
//   },
        "xi_api_key": "2199b08a74cb6146b3d93edf76b523ba", // replace with your API key
    };

    console.log(JSON.stringify(bosMessage));

    socket.send(JSON.stringify(bosMessage));

    // 3. Send the input text message ("Hello World")
    const textMessage = {
        "text":  text,
    };

    socket.send(JSON.stringify(textMessage));

    // 4. Send the EOS message with an empty string
    const eosMessage = {
        "text": ""
    };

    socket.send(JSON.stringify(eosMessage));
};

// function base64ToBlob(base64, type = 'audio/mpeg') {
//     const binaryString = window.atob(base64);
//     const len = binaryString.length;
//     const bytes = new Uint8Array(len);
//     for (let i = 0; i < len; i++) {
//         bytes[i] = binaryString.charCodeAt(i);
//     }
//     return new Blob([bytes], {type: type});
// }


// 5. Handle server responses
socket.onmessage = function(event) {
        const response = JSON.parse(event.data);

        if (response.audio) {
            // Decode base64 audio data and accumulate it
            const audioData = atob(response.audio);
            for (let i = 0; i < audioData.length; i++) {
                accumulatedAudioData.push(audioData.charCodeAt(i));
            }
        }

        if (response.isFinal) {
            // All audio data has been received; convert to Blob and play
            const audioBlob = new Blob([new Uint8Array(accumulatedAudioData)], { type: 'audio/mp3' });
            const audioUrl = URL.createObjectURL(audioBlob);
            let audio = new Audio(audioUrl);
        // audio.play().catch(e => console.error("Audio playback error", e));

        audio.onended = () => {
            playNextInQueue(); // Ensure the next item in the queue plays after one finishes.
        };
        if (!currentAudio || currentAudio.ended) {
            currentAudio = audio;
            audio.play().catch(e => console.error("Audio playback error", e));
        } else {
            audioQueue.push(audio);
        }

        }
    };
    function playNextInQueue() {
        if (audioQueue.length > 0) {
            currentAudio = audioQueue.shift();
            currentAudio.play().catch(e => console.error("Audio playback error", e));
        }
    }

    // function playAudio(audioUrl) {
    //     const audio = new Audio(audioUrl);
    //     audio.play().catch(e => console.error("Audio playback error", e));
    // }

// Handle errors
socket.onerror = function (error) {
    console.error(`WebSocket Error: ${error}`);
};

// Handle socket closing
socket.onclose = function (event) {
    if (event.wasClean) {
        console.info(`Connection closed cleanly, code=${event.code}, reason=${event.reason}`);
    } else {
        console.warn('Connection died');
    }
};



   }


   function resetAudioEnvironment() {
        if (currentAudio && !currentAudio.paused) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
        }
        audioQueue.forEach(audio => {
            audio.pause();
            audio.currentTime = 0;
        });
        audioQueue = [];
        currentAudio = null;
    }




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
    resetAudioEnvironment();
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

                    if (data.transcript=='' || data.transcript=='Thanks for watching!'|| typeof data.transcript !== 'string' ) {
                        
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

// function playTextToSpeech(text) {
//     const audioSrc = `{{url('/text-to-speech')}}?text=${encodeURIComponent(text)}`;
//     const audio = new Audio(audioSrc);
//     audio.play();
// }

// function sendTextToSpeech(text) {
//     return new Promise((resolve, reject) => {
//         const ttsSrc = `{{url('/text-to-speech')}}?text=${encodeURIComponent(text)}`;
//         const audio = new Audio(ttsSrc);
//         audio.oncanplaythrough = resolve; // Resolve the promise when the audio is ready to play
//         audio.onerror = reject; // Reject the promise on error
//         audio.play().catch(e => reject(e));
//     });
// }

    </script>
    

    
</body>
</html>