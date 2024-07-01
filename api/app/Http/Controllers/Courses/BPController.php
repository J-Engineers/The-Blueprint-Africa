<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Models\Courses\BP;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Requests\Courses\StoreBPRequest;
use App\Http\Requests\Courses\UpdateBPRequest;
use Symfony\Component\HttpFoundation\Response;

class BPController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $discount = BP::all();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'BP Found',
            'data' => $discount
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBPRequest $request, BP $bP)
    {
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }


        $request->validated();

        $bp = $bP::all();
        if($bp){
            foreach ($bp as  $value) {
                $bP::where('id', $value->id)->delete();
            }
        }
        $created = $bP::create([
            'id' => (string)Str::uuid(),
            'rate' => $request->rate
        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'BP created',
            'data' => $created
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Display the specified resource.
     */
    public function show(BP $bP, Request $request)
    {

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user = $bP::where('id', $request->bp_id)->first();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'BP Found',
            'data' => $search_user
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BP $bP)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBPRequest $request, BP $bP)
    {
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $request->validated();

        $course = $bP::where('id', $request->bp_id)->first();
        $course->update([
            'rate' => $request->rate

        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'BP updated',
            'data' => $course
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BP $bP, Request $request)
    {
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $bP::where('id', $request->bp_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'BP removed',
            'data' => []
        ], Response::HTTP_ACCEPTED);
    }
}
