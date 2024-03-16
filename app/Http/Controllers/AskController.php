<?php

namespace App\Http\Controllers;
use App\Services\AssistantService;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
class AskController extends Controller
{
    public function __construct(AssistantService $assistantService) {
        $this->assistantService = $assistantService;
    }

    public function st(Request $request)
    {

        $messa= '';
        $question = $request->query('question');
        // composer require openai-php/laravel --with-all-dependencies
        // for adding query to the thread
$threadid = $request->query('threadid');
$assistantId = $request->query('assistantId');

$addmessage = $this->assistantService->addMessageToThread($threadid, $question);

  // Run the assistant on the thread to process the message and get a response
  $runId = $this->assistantService->runAssistantOnThread($assistantId, $threadid);

  $runData = $this->assistantService->pollRunStatus($runId, $threadid);

//   Log::error($runData);
if ($runData['status'] === 'completed') {
    $messages = $this->assistantService->getThreadMessages($threadid);
    // Process and store the latest assistant's responses
    $messa= $this->storeAssistantResponses($messages);
    // event(new \App\Events\IsTyping(false, true, $channel));
    // Log::error($messa);
    return response()->stream(function () use ($messa) {

        foreach ($messa as $msg) {
                    $text = $msg['content'][0]['text']['value'];
                    if (connection_aborted()) {
                        break;
                    }

                    echo "event: update\n";
                            echo 'data: ' . $text;
                            echo "\n\n";
                            ob_flush();
                            flush();
                               // Convert text to speech and stream audio
                        // $this->streamTextToSpeech($text);
                        }
            
                        echo "event: update\n";
                        echo 'data: <END_STREAMING_SSE>';
                        echo "\n\n";
                        ob_flush();
                        flush();
                    
                    }, 200, [
                        'Cache-Control' => 'no-cache',
                        'X-Accel-Buffering' => 'no',
                        'Content-Type' => 'text/event-stream',
                    ]);

}
// real code
       
        // return response()->stream(function () use ($question) {
        //     $stream = OpenAI::chat()->createStreamed([
        //         'model' => 'gpt-3.5-turbo',
        //         'temperature' => 0.8,
        //         'messages' => [
        //             [
        //                 'role' => 'user',
        //                 'content' => $question
        //             ]
        //         ],
        //         'max_tokens' => 1024,
        //     ]);
        //     // $buffer = "";
        //     foreach ($stream as $response) {
        //         $text = $response->choices[0]->delta->content;
        //         if (connection_aborted()) {
        //             break;
        //         }

        //         echo "event: update\n";
        //         echo 'data: ' . $text;
        //         echo "\n\n";
        //         ob_flush();
        //         flush();
        //            // Convert text to speech and stream audio
        //     // $this->streamTextToSpeech($text);
        //     }

        //     echo "event: update\n";
        //     echo 'data: <END_STREAMING_SSE>';
        //     echo "\n\n";
        //     ob_flush();
        //     flush();
        
        // }, 200, [
        //     'Cache-Control' => 'no-cache',
        //     'X-Accel-Buffering' => 'no',
        //     'Content-Type' => 'text/event-stream',
        // ]);
    }



//  


// 

protected function storeAssistantResponses($messages)
    {

        $assistantmess= '';
        // Find the index of the last user message
        $lastUserMessageIndex = null;
        foreach ($messages as $index => $msg) {
            if ($msg['role'] === 'user') {
                $lastUserMessageIndex = $index;
                break;
            }
        }

        // Filter assistant messages that appear after the last user message
        $assistantMessages = array();
        if ($lastUserMessageIndex !== null) {
            for ($i = 0; $i < $lastUserMessageIndex; $i++) {
                if ($messages[$i]['role'] === 'assistant') {
                    $assistantMessages[] = $messages[$i];
                }
            }
        }

        // Reverse the order of the assistant messages
        $assistantMessages = array_reverse($assistantMessages);
        $assistantmess= '';
        // Process each assistant message
        foreach ($assistantMessages as $msg) {
            // $aiResponseMessage = new \App\Models\Message();
            // $aiResponseMessage->message_creatorid = $adminPrimaryId; // Assuming ID 1 is your admin
            // $aiResponseMessage->message_creator_uniqueid = $adminUniqueId;
            // $aiResponseMessage->message_unique_id = str_unique();
            // $aiResponseMessage->message_timestamp = time();
            // $aiResponseMessage->message_source = $adminUniqueId;
            // $aiResponseMessage->message_target = $userUniqueId;
            // $aiResponseMessage->message_text = $msg['content'][0]['text']['value'];
            // $aiResponseMessage->message_type = 'text';
            // $aiResponseMessage->conversation_id = $conversationId;
            // $aiResponseMessage->save();
             // Assuming $msg['content'][0]['text']['value'] contains the message text
    $messageText = $msg['content'][0]['text']['value'];
    $assistantmess .= $messageText . " "; // Append the message and a newline character to the string


            // event(new \App\Events\MessageSent($aiResponseMessage, null, app('currentTenant')->subdomain . '-' . $conversationId));

            // event(new \App\Events\MessageSent($aiResponseMessage, null, app('currentTenant')->subdomain . '-conversations'));
        }

       return  $assistantMessages;

       
    }






















}
