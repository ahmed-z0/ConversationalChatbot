<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhisperController extends Controller
{
    public function transcribe(Request $request)
    {
        if (!$request->hasFile('audio')) {
            return response()->json(['error' => 'No audio file provided'], 400);
        }

        $audioFile = $request->file('audio');
        $filePath = $audioFile->getRealPath();

        // Initialize CURL session for Whisper API endpoint
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . env('OPENAI_API_KEY'), // If your API requires an Authorization header
                'Content-Type: multipart/form-data', // This header is usually managed automatically by CURL when using CURLOPT_POSTFIELDS with CURLFile
            ],
            CURLOPT_POSTFIELDS => [
                'file' => new \CURLFile($filePath, $audioFile->getClientMimeType(), $audioFile->getClientOriginalName()),
                'model' => 'whisper-1',
                // 'prompt'=> '',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        if ($err) {
            Log::error("CURL Error: $err");
            return response()->json(['error' => "CURL Error: $err"], 500);
        } else {
            // Log the raw response for inspection
            Log::info("Whisper API Raw Response: " . $response);

            $responseData = json_decode($response, true);

            // Assuming the API returns the transcript directly in the response
            // Adjust the response handling based on the actual structure you find in the logs
            if (isset ($responseData['text'])) {
                return response()->json(['transcript' => $responseData['text']]);
            } else {
                Log::error("Unexpected response structure from Whisper API");
                return response()->json(['error' => 'Unexpected response structure from Whisper API'], 500);
            }
        }

        curl_close($curl);
    }
}
