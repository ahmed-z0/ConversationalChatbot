<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class HomeController extends Controller
{




    public function home()
    {


        return view('whisper.stt');


    }


    public function chatbot()
    {


        return view('chatbot.chatbot');


    }



    public function ct(Request $request)
    {
        $prompt = $request->message;
        // $question= $request->topic;

        // $userId = Auth::id();

        // $pd= DB::table('purchased_packages')
        // ->select('purchased_packages.*')
        // ->where('purchased_packages.userid', '=',$userId)
        // ->where('purchased_packages.status', '=',1)
        // ->first(); //its necessary







        // $ad= DB::table('api')
        // ->select('api.*')
        // ->where('api.id', '=',1)
        // ->first(); //its necessary



        // dd($apik);


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.openai.com/v1/completions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n    \"model\": \"gpt-3.5-turbo-instruct\",\n    \"prompt\": \".$prompt.'\",\n    \"max_tokens\": 4000,\n    \"temperature\": 0\n  }",
            CURLOPT_HTTPHEADER => array(
                "authorization: " . 'Bearer ' . env('OPENAI_API_KEY'),
        ,
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 15e808dd-1ebf-6891-595c-d6787e0d1ea8"
            ),
        )
        );

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {

            $result = json_decode($response);
            // echo($result->choices[0]->text);
            //   echo $result->choices;
            //    dd($result);

            $data = $result->choices[0]->text;

            // for text to speech
            $client = new Client();
            $response = $client->post('https://api.openai.com/v1/audio/speech', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'tts-1',
                    'input' => $data,
                    'voice' => 'alloy',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $audioContent = $response->getBody()->getContents();
                // Generate a unique file name
                $fileName = uniqid() . '.mp3';
                $audioPath = public_path('audio/' . $fileName);
                file_put_contents($audioPath, $audioContent);

                return response()->json([
                    'audioPath' => asset('public/audio/' . $fileName),
                    'data' => $data,
                ]);

                // return view('app', [
                //     'audioPath' => asset('audio/speech.mp3'),
                //     'text' => $text,
                // ]);
            } else {
                return response()->json($data);
            }


            //  // Save the chatgpt data
//  $chatgptdata = new ChatgptData;
//  $chatgptdata->user_id = $userId;
//  $chatgptdata->package_id = $pd->id;
//  $chatgptdata->prompt=$question;
//  $chatgptdata->response=$data;
//  $chatgptdata->save();


            // if (Auth::user()->roles == 1) {
//     $chatgptdata = new ChatgptData;
//  $chatgptdata->user_id = $userId;
//  $chatgptdata->prompt=$question;
//  $chatgptdata->response=$data;
//  $chatgptdata->save();
//  } else {
//     # code...
//     $chatgptdata = new ChatgptData;
//     $chatgptdata->user_id = $userId;
//     $chatgptdata->package_id = $pd->id;
//     $chatgptdata->prompt=$question;
//     $chatgptdata->response=$data;
//     $chatgptdata->save();
//  }


            // return response()->json($data);
        }
    }


    public function chatd(Request $request)
    {

        $text = $request->query('text');

        // Initialize the Guzzle HTTP client
        $client = new Client();

        try {
            // Prepare the request body
            $body = json_encode([
                "model_id" => "eleven_monolingual_v1",
                "voice_settings" => [
                    "similarity_boost" => 0.8,
                    "stability" => 0.8
                ],
                // Assuming `$text` is the text you want to convert to speech
                "text" => $text
            ]);

            // Send a POST request to the ElevenLabs Text-to-Speech API
            $response = $client->post('https://api.elevenlabs.io/v1/text-to-speech/1J4RetdHwFoQxvr6VQT7/stream', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'xi-api-key' => '409f724084beb97d6043ce8ea3d513f9',
                ],
                'body' => $body,
                'stream' => true, // Enable response streaming
            ]);

            // Stream the response back to the client
            return response()->stream(function () use ($response) {
                while (!$response->getBody()->eof()) {
                    echo $response->getBody()->read(2048);
                    ob_flush();
                    flush();
                }
            }, 200, [
                'Content-Type' => 'audio/mpeg',
            ]);
        } catch (\Exception $e) {
            Log::error('Error connecting to ElevenLabs: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to connect to the Text-to-Speech service'], 500);
        }
    }





























}
