<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;
use App\Jobs\SendOtpEmail;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register','forgotPassword','varifyOtp','submitResetPasswordForm']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);
        try{
            $login_type = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL ) 
            ? 'email' 
            : 'phone';

            $request->merge([
                $login_type => $request->input('login')
            ]);


            $credentials = $request->only($login_type, 'password');

            $token = Auth::attempt($credentials);
            //dd($token);
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your login credentials are not correct, please try again with correct credentials',
                ], 401);
            }

            $user = Auth::user();
            return response()->json([
                    'status' => 'success',
                    'user' => $user,
                    'authorisation' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ]
                ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }

    }

    public function register(Request $request){
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|min:8|max:13|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);
        try{
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'lat' => $request->lat,
                'long' => $request->long,
            ]);

            $token = Auth::login($user);
            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
        }
        catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function forgotPassword(Request $request){
        $request->validate(
            [
            'email' => 'required|string|email|exists:users,email',
            ],
            [
                'email.exists' => 'The email is not exist.'
            ],
        );
        // digit otp
        $otp = rand(1000,9999);
        \Log::info("otp = ".$otp);
        try{
            $user = User::where('email','=',$request->email)->update(['otp' => $otp]);

            if($user){
                $mail_details = [
                    'to' => $request->email,
                    'subject' => 'Testing Application OTP',
                    'otp' => $otp
                ];
                SendOtpEmail::dispatch($mail_details);        
                return response(["status" => 200, "message" => "OTP sent successfully"]);
            }
            else{
                return response(["status" => 401, 'message' => 'Invalid']);
            }
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function varifyOtp(Request $request){
        
        $request->validate(
            [
            'otp' => 'required|exists:users,otp',
            ],
            [
                'otp.exists' => 'The OTP is not correct.'
            ],
        );
        try{
            $user  = User::where([['otp','=',$request->otp]])->first();
            $token = Auth::login($user);
            if($user){
                // User::where('otp','=',$request->otp)->update(['otp' => null]);
                return response(["status" => 200, "message" => "OTP is verfied",'otp' => $request->otp]);
            }
            else{
                return response(["status" => 401, 'message' => 'Invalid']);
            }
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function submitResetPasswordForm(Request $request)
    {
        $request->validate([
            'otp' => 'required',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        try{
            $updatePassword = User::where(['otp' => $request->otp])->first();

            if(!$updatePassword){
                return response(["status" => 401, 'message' => 'Invalid OTP, Please add your last otp']);
            }

            $user = User::where(['otp' => $request->otp])
                        ->update(['password' => Hash::make($request->password),'otp' => null]);
                        

            return response(["status" => 200, "message" => "Password reset successfully"]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function updateProfile(Request $request, $id){
        // dd('sdsds');
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,id,'.$id,
            'phone' => 'required|min:8|max:13|unique:users,id,'.$id,
        ]);
        try{
            if(empty($id) || $id==''){
                return response(["status" => 401, 'message' => 'Invalid User']);
            }
            $user = User::where(['id'=>$id])->update([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'lat' => $request->lat,
                'long' => $request->long,
            ]);
            $userData = User::where(['id'=>$id])->first();
            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'user' => $userData,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function getMyFriendList(){
        $user = Auth::user();
        try{
            $getUserFriend = $user->friends()->get();
            $count = Auth::user()->friends->count();
            if($count>0){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Friend list fetched successfully',
                    'user' => $getUserFriend,
                ]);
            }
            return response()->json([
                'status' => 'false',
                'message' => 'No friends are added',
                'user' => $getUserFriend,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
        
    }

    public function getAddFriend($id)
    {
        try{
            $user = User::find($id);
            $friendAdded = Auth::user()->addFriend($user);
            return response()->json([
                'status' => 'true',
                'message' => 'Friend request sent',
                'user' => $user,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function removeFriend($id){
        try{
            $user = User::find($id);
            $friendAdded = Auth::user()->removeFriend($user);
            return response()->json([
                'status' => 'true',
                'message' => 'Friend Removed successfully',
                'user' => $user,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function searchUserForFriend(Request $request){
        try{
            $auth = Auth::user();
            
            $name = $request->get('name');
            $email = $request->get('email');
            $phone = $request->get('phone');
            $distance = $request->get('distance');
            
            $user = User::Query();
            
            $user->where('id','!=',$auth->id);
            if(!empty($email)){
                $user->where('email','like',$email.'%');
            }
            if(!empty($name)){
                $user->where('firstname','like','%'.$name.'%');
                $user->orWhere('lastname','like','%'.$name.'%');
            }
            
            if(!empty($phone))
                $user->where('phone','like',$phone.'%');

            if(!empty($distance))
                $user->where('distance','=',$distance);
            
            // filter user that are already in friend list
            $user->whereNotIn('id', Auth::user()->friends->modelKeys());
            $searchedUser = $user->orderBy('id','desc')->paginate(20);
            if ($searchedUser->total()==0) {
        
                return response()->json([
                    'status' => 'false',
                    'message' => 'No data found, related to your search',
                    'user' => null,
                ]);
            }
            return response()->json([
                'status' => 'true',
                'message' => 'Searched users fetched successfully',
                'user' => $searchedUser,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function acceptRequest($id){
        try{
            // 2 = user accepting the friend request
            $user = Auth::user();
            $pivotUser = User::find($id); 
            $user->friends()->updateExistingPivot($pivotUser->id, [
                'accepted' => 2,
            ]);
            return response()->json([
                'status' => 'true',
                'message' => "Request Accepted",
                'user' => $pivotUser,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function RejectRequest($id){
        try{
            // 3 = user rejecting the friend request
            $user = Auth::user();
            $pivotUser = User::find($id); 
            $user->friends()->updateExistingPivot($pivotUser->id, [
                'accepted' => 3,
            ]);
            return response()->json([
                'status' => 'true',
                'message' => "Request Rejected",
                'user' => $pivotUser,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

    public function viewUserProfile($id){
        try{
            // $id = user id that needs to view
            $user = User::find($id); 
    
            return response()->json([
                'status' => 'true',
                'message' => "Profile Data",
                'user' => $user,
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'false',
                'message' => $e->getMessage(),
                'user' => null,
            ]);
        }
    }

}
