<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Real-time Speech to Text with Silence Detection</h2>
    <div id="micButton" class="mic-btn green"><i class="fas fa-microphone"></i></div>
    <textarea id="transcriptionResult" class="form-control mt-3" rows="10" readonly></textarea>
</div>

<script>
    $(document).ready(function() {
        let audioContext;
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;
        let audioInput, analyser, silenceDetectionNode;
        let silenceThreshold = -45; // Silence threshold in dB
        let isSpeechDetected = false;
        let silenceTimeoutId;
        let silenceDelay = 2000; // Delay in milliseconds to wait before stopping the recording after silence is detected
    
        $('#micButton').click(async function() {
            if (!isRecording) {
                await startRecording();
            } else {
                stopRecordingManually();
            }
        });
    
        async function startRecording() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                await audioContext.audioWorklet.addModule("{{asset('public/assets')}}/audio-processor.js"); // Path to your audio processor worklet
            }
    
            navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
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
                    $('#transcriptionResult').append(data.transcript);
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
