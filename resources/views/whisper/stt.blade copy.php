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
    let audioInput, analyser, scriptProcessor;
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
            if (audioContext.state === "suspended") {
                await audioContext.resume();
            }
        }

        navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
            mediaRecorder = new MediaRecorder(stream);
            audioInput = audioContext.createMediaStreamSource(stream);
            analyser = audioContext.createAnalyser();
            scriptProcessor = audioContext.createScriptProcessor(2048, 1, 1);

            audioInput.connect(analyser);
            analyser.connect(scriptProcessor);
            scriptProcessor.connect(audioContext.destination);

            scriptProcessor.onaudioprocess = processAudio;
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
        // Do not reset the recording state here to keep the button red
    };

    $('#micButton').removeClass('green').addClass('red');
    $('#micButton i').removeClass('fa-microphone').addClass('fa-stop');
    isRecording = true;
}

function processAudio(event) {
    if (!isRecording) return;

    const inputData = event.inputBuffer.getChannelData(0);
    const inputDataLength = inputData.length;
    let total = 0;

    for (let i = 0; i < inputDataLength; i++) {
        total += Math.abs(inputData[i]);
    }

    let average = total / inputDataLength;
    let averageDb = 20 * Math.log10(average);

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
    resetRecordingState(); // Only reset the state when manually stopping the recording
}
function resetRecordingState() {
    $('#micButton').removeClass('red').addClass('green');
    $('#micButton i').removeClass('fa-stop').addClass('fa-microphone');
}

    function sendAudioToServer(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob);
        // Adjust the URL to your server endpoint
        formData.append('_token', '{{ csrf_token() }}'); // When using Blade templates
        $.ajax({
            url: "{{ url('/transcribe') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                // $('#transcriptionResult').val(data.transcript || "No transcription available.");
                $('#transcriptionResult').val(data.transcript);
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
