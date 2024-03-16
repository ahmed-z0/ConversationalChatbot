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
            height: calc(100vh - 120px); /* Adjust based on total height of other elements */
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
    </style>
</head>
<body class=" bg-light">
    <div class="container">
        <div class="col-md-8">
            <div class="bg-white rounded-3 p-4 shadow" style="width: 100%;">
                <div id="chatOutput" class="message-container"></div>
                <form id="chatForm" class="mt-4 d-flex gap-3">
                    <input required type="text" name="input" class="form-control" placeholder="Type your question here...">
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>
    </div>
{{-- -------------------------For chat bot------------------------ --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let audioQueue = [];
    let isPlaying = false;

    function stopAllAudios() {
        audioQueue.forEach(audio => {
            audio.pause(); // Stops playing the audio
            audio.currentTime = 0; // Resets the audio play time
        });
        audioQueue = []; // Clears the queue
        isPlaying = false; // Resets the playing state
    }

    $("#chatForm").submit( function(event) {
        event.preventDefault();
        const inputVal = $("input[name='input']").val().trim();
        if (!inputVal) return;
 stopAllAudios();
        // Display User Message
        const userMessageHtml = `<div class="user-message"><strong>User:</strong> ${inputVal}</div>`;
        $("#chatOutput").append(userMessageHtml);
        
        // Clear input field
        $("input[name='input']").val("");

        // Create AI message container for new response
        const aiMessageContainer = $("<div class='ai-message'><strong>AI:</strong> </div>");
        $("#chatOutput").append(aiMessageContainer);

        const encodedQuestion = encodeURIComponent(inputVal);
        const source = new EventSource("{{url('/ask')}}?question=" + encodedQuestion);
        let aiResponseBuffer = "";
        let lastSentText = "";
        source.addEventListener("update",  function (event) {
            if (event.data === "<END_STREAMING_SSE>") {
                source.close(); // Close the SSE connection when done
                // playTextToSpeech(aiResponseText);
                if (aiResponseBuffer.trim() && aiResponseBuffer !== lastSentText) {
                    enqueueTextToSpeech(aiResponseBuffer);
                    lastSentText = aiResponseBuffer;
                }
                playNextInQueue(); // Attempt to play the next audio after the last text is enqueued
            } else {
                // // Directly append received AI Response part
                // aiResponseText += event.data;
                aiResponseBuffer += event.data;
                let sentences = aiResponseBuffer.split(/(?<=\.\s)/);
                
                if (sentences.length > 1) {
                    sentences.slice(0, -1).forEach(sentence => {
                        if (sentence.trim() && sentence !== lastSentText) {
                            enqueueTextToSpeech(sentence);
                            lastSentText = sentence;
                        }
                    });
                    aiResponseBuffer = sentences[sentences.length - 1];
                }

                aiMessageContainer.append(document.createTextNode(event.data));
            //   // Trigger text-to-speech
            //   const ttsSrc = `{{url('/text-to-speech')}}?text=${encodeURIComponent(event.data)}`;
            //     const audio = new Audio(ttsSrc);
            //     audio.play().catch(e => console.error("Audio playback error", e));
        
            }
        });

         // Listen for audio updates
         source.addEventListener("audioUpdate", function(event) {
            var data = JSON.parse(event.data);
            if(data.audioUri) {
                // Play the received audio URI
                var audio = new Audio(data.audioUri);
                audio.play().catch(e => console.error("Audio playback error", e));
            } else if(data.error) {
                console.error("Text-to-Speech Error:", data.error);
            }
        });

        source.onerror = function() {
            // Handle error or close event
            source.close(); // Close the connection on error or completion
        };












    });

    function enqueueTextToSpeech(text) {
        let ttsSrc = `{{url('/text-to-speech')}}?text=${encodeURIComponent(text)}`;
        audioQueue.push(new Audio(ttsSrc));
        if (!isPlaying) {
            playNextInQueue();
        }
    }

    function playNextInQueue() {
        if (audioQueue.length > 0 && !isPlaying) {
            isPlaying = true;
            let audio = audioQueue.shift();
            audio.play().then(() => {
                audio.onended = function() {
                    isPlaying = false;
                    playNextInQueue();
                };
            }).catch(e => {
                console.error("Audio playback error", e);
                isPlaying = false;
                playNextInQueue();
            });
        }
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