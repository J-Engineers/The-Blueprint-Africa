<?php

namespace App\Http\Controllers\Courses;

use Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Courses\BlueprintCourses;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Courses\StoreBlueprintCoursesRequest;
use App\Http\Requests\Courses\UpdateBlueprintCoursesRequest;

class BlueprintCoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courses = BlueprintCourses::all();

        if($request->is_discount == true){
            foreach($courses as $course){
                $course->price = $course->price * ($course->discount_rate / 100);
            }
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Courses Found',
            'data' => ['courses' => $courses, 'scholarship' => $request->is_discount]
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
    public function store(StoreBlueprintCoursesRequest $request)
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


        if(!$request->hasfile('image'))
        {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Course image not Found',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if(!$request->hasfile('outline_link'))
        {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Course outline not Found',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $check = BlueprintCourses::where(
            [
                ['name', '=', $request->name],
                ['description', '=', $request->description]
            ]
        )->select('id')->get();

        if($check && count($check) > 0){
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Already registered',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $file = $request->file('image');
        $name=time().$file->getClientOriginalName();
        $filePath = 'images/' . $name;
        $disk = Storage::disk('s3');
        $disk->put($filePath, file_get_contents($file));
        $base_path = $disk->url($filePath);


        $file = $request->file('outline_link');
        $name=time().$file->getClientOriginalName();
        $filePath = 'images/' . $name;
        $disk = Storage::disk('s3');
        $disk->put($filePath, file_get_contents($file));
        $base_path1 = $disk->url($filePath);


        


        $created = BlueprintCourses::create([
            'id' => (string)Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'outline_link' => $base_path1,
            'price' => $request->price,
            'discount_rate' => $request->discount_rate,
            'duration' => $request->duration,
            'image' => $base_path,
            'referral_bonus_rate' => $request->referral_bonus_rate
        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Course created',
            'data' => $created
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Display the specified resource.
     */
    public function show(BlueprintCourses $blueprintCourses, Request $request)
    {
        $courses = $blueprintCourses::where('id', $request->course_id)->first();

        if($request->is_discount == true){
            foreach($courses as $course){
                $course->price = $course->price * ($course->discount_rate / 100);
            }
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Course Found',
            'data' => ['course' => $courses, 'scholarship' => $request->is_discount]
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BlueprintCourses $blueprintCourses)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlueprintCoursesRequest $request, BlueprintCourses $blueprintCourses)
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

        if(!$request->hasfile('image'))
        {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Course image not Found',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if(!$request->hasfile('outline_link'))
        {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Course outline not Found',
            ], Response::HTTP_UNAUTHORIZED);
        }

        

        $file = $request->file('image');
        $name=time().$file->getClientOriginalName();
        $filePath = 'images/' . $name;
        $disk = Storage::disk('s3');
        $disk->put($filePath, file_get_contents($file));
        $base_path = $disk->url($filePath);


        $file = $request->file('outline_link');
        $name=time().$file->getClientOriginalName();
        $filePath = 'images/' . $name;
        $disk = Storage::disk('s3');
        $disk->put($filePath, file_get_contents($file));
        $base_path1 = $disk->url($filePath);

        $course = $blueprintCourses::where('id', $request->course_id)->first();
        $course->update([
            'name' => $request->name,
            'description' => $request->description,
            'outline_link' => $base_path1,
            'price' => $request->price,
            'discount_rate' => $request->discount_rate,
            'duration' => $request->duration,
            'image' => $base_path

        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Course updated',
            'data' => $course
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlueprintCourses $blueprintCourses, Request $request)
    {
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $blueprintCourses::where('id', $request->course_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Course removed',
            'data' => []
        ], Response::HTTP_ACCEPTED);
    }
}
