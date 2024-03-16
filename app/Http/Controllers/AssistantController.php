<?php

namespace App\Http\Controllers;
use App\Models\Assistants;
use Illuminate\Http\Request;
use App\Services\AssistantService;
use Illuminate\Support\Facades\Http;

class AssistantController extends Controller
{
    //
    public function __construct(AssistantService $assistantService) {
        $this->assistantService = $assistantService;
    }


    public function assistants()
    {

        try {
            $assistantsList = $this->assistantService->assistantList();
            return view('assistantapi.assistants', ['assistantsList' => $assistantsList]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }


    //     //  Fetch only assistants where 'status' is 1
    // $assistants = Assistants::where('status', 1)->get();
 
        


    }

    
    // public function createassistant()
    // {

    //      // Fetch only assistants where 'status' is 1
    // // $assistants = Assistants::where('status', 1)->get();

 
    //     return view('assistantapi.create_assistant');
        


    // }



    public function createassistant()
    {

         // Fetch only assistants where 'status' is 1
    // $assistants = Assistants::where('status', 1)->get();

 
        return view('assistantapi.create_assistant');
        


    }



// store

public function store(Request $request) {
    $validated = $request->validate([
        'name' => 'required|string',
        'description' => 'required|string',
        'file' => 'nullable|file',
    ]);

    try {
        $file = $request->hasFile('file') ? $request->file('file') : null;
        $assistant = $this->assistantService->createAssistant($validated['name'], $validated['description'], $file);

        return response()->json(['success' => true, 'message' => 'Assistant created successfully!', 'data' => $assistant]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}





























}
