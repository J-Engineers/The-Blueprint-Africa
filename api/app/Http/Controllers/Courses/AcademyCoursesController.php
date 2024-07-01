<?php

namespace App\Http\Controllers\Courses;

use Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Courses\AcademyCourses;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Courses\StoreAcademyCoursesRequest;
use App\Http\Requests\Courses\UpdateAcademyCoursesRequest;
use Illuminate\Http\Request;


class AcademyCoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       
        $courses = AcademyCourses::all();
        

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
    public function store(StoreAcademyCoursesRequest $request)
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

        
        $check = AcademyCourses::where(
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


        $created = AcademyCourses::create([
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
    public function show(AcademyCourses $academyCourses, Request $request)
    {

        $courses = $academyCourses::where('id', $request->course_id)->first();

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
    public function edit(AcademyCourses $academyCourses)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAcademyCoursesRequest $request, AcademyCourses $academyCourses)
    {

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

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

        $course = $academyCourses::where('id', $request->course_id)->first();
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
    public function destroy(AcademyCourses $academyCourses, Request $request)
    {
        //

        
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $academyCourses::where('id', $request->course_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Course removed',
            'data' => []
        ], Response::HTTP_ACCEPTED);

    }
}
