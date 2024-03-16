<?php

namespace App\Services;

use App\Models\Assistants;
use App\Models\threads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AssistantService {
    private $openAiApiKey;
    private $baseOpenAiUrl;

    public function __construct() {
        $this->openAiApiKey = env('OPENAI_API_KEY');
        $this->baseOpenAiUrl = 'https://api.openai.com/v1';
    }

    private function uploadFileToOpenAI($file) {
        $filePath = $file->getRealPath();
        $filename = $file->getClientOriginalName();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openAiApiKey,
        ])->attach(
            'file', file_get_contents($filePath), $filename
        )->timeout(60)->post($this->baseOpenAiUrl . '/files', [
            'purpose' => 'assistants',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['id']; // Return the file ID from OpenAI
        } else {
            // Handle errors or unsuccessful response
            throw new \Exception("Failed to upload file to OpenAI: " . $response->body());
        }
    }

    public function createAssistant($name, $description, $file = null) {
        $fileIds = [];
        if ($file) {
            $fileId = $this->uploadFileToOpenAI($file);
            if ($fileId) {
                $fileIds[] = $fileId;
            }
        }

        $body = [
            'name' => $name,
            'instructions' => $description,
            'model' => 'gpt-3.5-turbo',
            'tools' => [['type' => 'code_interpreter']],
        ];

        if (!empty($fileIds)) {
            $body['file_ids'] = $fileIds;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openAiApiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v1', // Add this line
        ])->timeout(60)->post($this->baseOpenAiUrl . '/assistants', $body);

        if ($response->successful()) {
            $data = $response->json();
            $assistantId = $data['id'];

            // Save the assistant name and ID in the database
            $assistant = Assistants::create([
                'name' => $name,
                'assistant_id' => $assistantId,
            ]);

            return $assistant; // Return the assistant model instance
            Log::error('success connecting to assistant: ' . $assistant);
        } else {
            throw new \Exception("Failed to create assistant: " . $response->body());
            Log::error('Error connecting to assistant: ' . $response->body());
        }
    }




    public function assistantlist() {


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->openAiApiKey,
            'OpenAI-Beta' => 'assistants=v1',
        ])->get('https://api.openai.com/v1/assistants', [
            'order' => 'desc',
            'limit' => 5,
        ]);

        if ($response->successful()) {
            $assistants = $response->json();
            // Do something with the assistants data, e.g., pass to a view or return as a response
            return $response->json()['data'];
        } else {
            // Handle errors
            return response()->json(['error' => 'Failed to fetch assistants'], 500);
        }




    }


    // Threads

    public function getOrCreateThread($assistantid)
    {
        $threads = DB::table('threads')
            ->where('assistant_id', $assistantid)
            ->first();

        // If a conversation already exists, return the thread ID
        if ($threads && $threads->thread_id) {
            return $threads->thread_id;
        }

        // Otherwise, create a new thread using the OpenAI API
        $threadId = $this->createThread();

        $newthread = new threads;

        $newthread->assistant_id = $assistantid;
        // $newthread->admin_unique_id = $adminUniqueId;
        $newthread->thread_id = $threadId;
        $newthread->save();

        return $threadId;
    }

    private function createThread()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openAiApiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v1'
        ])->post($this->baseOpenAiUrl . '/threads');

        if ($response->successful()) {
            $data = $response->json();
            return $data['id'] ?? null;
        }

        // Handle errors or unsuccessful response
        throw new \Exception("Failed to create OpenAI thread: " . $response->body());
    }





// add message in the thread

public function addMessageToThread($threadId, $messageContent, $role = 'user')
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->openAiApiKey,
        'Content-Type' => 'application/json',
        'OpenAI-Beta' => 'assistants=v1'
    ])->post($this->baseOpenAiUrl . "/threads/{$threadId}/messages", [
                'role' => $role,
                'content' => $messageContent
            ]);

    if ($response->successful()) {
        return $response->json();
    }

    // Handle errors or unsuccessful response
    throw new \Exception("Failed to add message to OpenAI thread: " . $response->body());
}



public function runAssistantOnThread($assistantId, $threadId)
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->openAiApiKey,
        'Content-Type' => 'application/json',
        'OpenAI-Beta' => 'assistants=v1'
    ])->post($this->baseOpenAiUrl . "/threads/{$threadId}/runs", [
                'assistant_id' => $assistantId
            ]);

    if ($response->successful()) {
        $data = $response->json();
        return $data['id'] ?? null; // Return the run ID
    }

    // Handle errors or unsuccessful response
    throw new \Exception("Failed to run assistant on OpenAI thread: " . $response->body());
}

public function checkRunStatus($runId)
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->openAiApiKey,
        'Content-Type' => 'application/json',
        'OpenAI-Beta' => 'assistants=v1'
    ])->get($this->baseOpenAiUrl . "/runs/{$runId}");

    if ($response->successful()) {
        $data = $response->json();
        return $data; // Return the run data
    }

    // Handle errors or unsuccessful response
    throw new \Exception("Failed to check run status on OpenAI: " . $response->body());
}

public function pollRunStatus($runId, $threadId, $attempts = 0)
{
    // Define intervals in seconds
    $intervals = [3, 5, 8, 10];

    // Get the interval based on the number of attempts
    $interval = $intervals[$attempts] ?? end($intervals); // Default to the last interval

    sleep($interval);

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->openAiApiKey,
        'Content-Type' => 'application/json',
        'OpenAI-Beta' => 'assistants=v1'
    ])->get($this->baseOpenAiUrl . "/threads/{$threadId}/runs/{$runId}");

    if ($response->successful()) {
        $data = $response->json();

        // Check if the run is completed
        if ($data['status'] === 'completed') {
            return $data;
        }

        // If not completed, recursively poll with an increased attempt count
        if ($attempts < count($intervals) - 1) {
            return $this->pollRunStatus($runId, $threadId, $attempts + 1);
        }
    }

    // Handle errors or unsuccessful response
    throw new \Exception("Failed to check run status on OpenAI: " . $response->body());
}



public function getThreadMessages($threadId, $limit = 4)
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->openAiApiKey,
        'Content-Type' => 'application/json',
        'OpenAI-Beta' => 'assistants=v1'
    ])->get($this->baseOpenAiUrl . "/threads/{$threadId}/messages", [
                'limit' => $limit,
                'order' => 'desc' // Retrieve the most recent messages first
            ]);

    if ($response->successful()) {
        return $response->json()['data'] ?? [];
    }

    // Handle errors or unsuccessful response
    throw new \Exception("Failed to retrieve messages from OpenAI thread: " . $response->body());
}





















}
