<?php

namespace App\Http\Controllers\Users;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\GetDetails;
use App\Http\Requests\Users\UpdatePhoto;
use App\Http\Requests\Users\UpdateDetails;
use App\Http\Requests\Users\ChangePassword;
use Storage;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function details(GetDetails $request){
        $request->validated();
        $auth = auth()->user()->id;
        
        $user = User::where('id', $auth)->first();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Found',
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function changePassword(ChangePassword $request){
        $request->validated();
        $auth = auth()->user()->id;

        $password = Hash::make($request->password);
        $user = User::where('id', $auth)->first();
        $user->update([
            'password' => $password
        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Password Reset Successful',
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function updateDetails(UpdateDetails $request){
        $request->validated();
        $auth = auth()->user()->id;
        $user = User::where('id', $auth)->first();
        $user->update([
            'title' => $request->title,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'address' => $request->address
        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Profile Updated',
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function updatePhoto(UpdatePhoto $request){
        $request->validated();
        $auth = auth()->user()->id;

        if(!$request->hasfile('image'))
        {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'File not Found',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $file = $request->file('image');
        $name=time().$file->getClientOriginalName();
        $filePath = 'images/' . $name;
        $disk = Storage::disk('s3');
        $disk->put($filePath, file_get_contents($file));
        $base_path = $disk->url($filePath);

        $user = User::where('id', $auth)->first();
        $user->update([
            'photo' => $base_path
        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Profile Picture Updated',
            'data' => $user
        ], Response::HTTP_OK);
    }
}
