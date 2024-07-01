<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GetUser;
use App\Http\Requests\Admin\GetUsers;
use App\Http\Requests\Admin\MakeAdmin;
use App\Http\Requests\Admin\RemoveUser;
use App\Http\Requests\Admin\CancelAdmin;
use App\Http\Requests\Admin\ActivateUser;
use App\Http\Requests\Admin\DeactivateUser;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    //
    public function getUsers(GetUsers $request){
        $request->validated();
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $users = User::where('verify_email', '=', 1)->select('email', 'first_name', 'last_name', 'phone', 'user_type', 'id', 'photo')->limit(100)->get();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Found',
            'data' => $users
        ], Response::HTTP_OK);
    }

    public function getUser(GetUser $request){
        $request->validated();
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user = User::where('id', $request->user_id)->select('email', 'first_name', 'last_name', 'phone', 'user_type', 'id', 'photo')->first();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Found',
            'data' => $search_user
        ], Response::HTTP_OK);
    }

    public function removeUser(RemoveUser $request){
        $request->validated();
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        User::where('id', $request->user_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Deleted',
            'data' => []
        ], Response::HTTP_OK);
    }

    public function deactivateUser(DeactivateUser $request){
        $request->validated();
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user = User::where('id', $request->user_id)->select('id', 'activate')->first();
        if(!$search_user){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user->update(['activate' => false]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Deactivated',
            'data' => $search_user
        ], Response::HTTP_OK);
    }

    public function activateUser(ActivateUser $request){
        $request->validated();
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user = User::where('id', $request->user_id)->select('id', 'activate')->first();
        if(!$search_user){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user->update(['activate' => true]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Activated',
            'data' => $search_user
        ], Response::HTTP_OK);
    }

    public function makeAdmin(MakeAdmin $request){
        $request->validated();
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user = User::where(
            [
                ['id', $request->user_id],
                ['user_type', 'admin'],
            ]
        )->select('id', 'user_type', 'is_admin')->first();
        if(!$search_user){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'User Not Found or User not an admin'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user->update(['is_admin' => true]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Activated',
            'data' => $search_user
        ], Response::HTTP_OK);
    }

    public function cancelAdmin(CancelAdmin $request){
        $request->validated();
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user = User::where('id', $request->user_id)->select('id', 'is_admin')->first();
        if(!$search_user){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }

        $search_user->update(['is_admin' => false]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Activated',
            'data' => $search_user
        ], Response::HTTP_OK);
    }
}
