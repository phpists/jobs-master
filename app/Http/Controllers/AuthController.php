<?php

namespace App\Http\Controllers;

use App\AuthProvider;
use App\Http\Resources\UsersResource;
use App\JobReview;
use App\Role;
use App\User;
use App\WebUserJobInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use Twilio\Rest\Client;

class AuthController extends Controller
{

    public function __construct()
    {
    }

    public function authenticate(Request $request, $return = false)
    {
        $credentials = $request->only('email', 'password');
        $credentials['enabled'] = 1;
        try {
            if (!$token = auth('web')->attempt($credentials)) {
                if($return) {
                    return [
                        'message' => ['error' => 'invalid_credentials'],
                        'code' => 400
                    ];
                }
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            if($return) {
                return [
                    'message' => ['error' => 'could_not_create_token'],
                    'code' => 500
                ];
            }
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        if($return) {
            return [
                'message' => compact('token'),
                'code' => 201
            ];
        }
        return response()->json(compact('token'));
    }

    public function register(Request $request)
    {
        $data = $request->all();
        $rules = [
            'phone' => 'required|string|numeric',
        ];
        if(!$request->is_hr && !isset($data['is_web'])) {
            $rules['role_id'] = 'required';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        if (!$user = User::where('phone', $data['phone'])->first()) {
            $user = new User;
            $user->enabled = 0;
        }
        $digitCount = 4;
        if (isset($data['is_web'])) {
            $digitCount = 6;
        }
        $digitCode = $this->generatePIN($digitCount);
        $user->digit_number = $digitCode;
        $user->phone = $data['phone'];
        if (isset($data['is_web'])) {
            $user->role_id = Role::USER_AFTER_SCHOOL;
            $user->name = $data['name'];
        } else {
            if($request->is_hr) {
                $user->role_id = Role::HR;
            } else {
                $user->role_id = $request->role_id;
            }
        }
        $user->password = Hash::make(rand(500000, 6966632555));
        $user->save();
        $this->sendSMSNotification($user->phone, $digitCode);

//        $token = JWTAuth::fromUser($user);
//        $user = new UsersResource($user);
        return response()->json(['code' => $digitCode], 201);
    }

    public function registerWithProvider(Request $request, $provider)
    {
        Log::info(json_encode($request->all()));
        $providerData = AuthProvider::where('name',$provider)->first();
//        if($provider == 'facebook') {
//            $rules = [
//                'user.email' => 'required|string|email',
//                'user.id' => 'required',
//                'role_id' => 'required',
//            ];
//
//        } elseif ($provider == 'google') {
//        }
        $rules = [
            'user.email' => 'required|string|email',
            'user.id' => 'required',
            'role_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            Log::info(json_encode($validator->errors()));
            return response()->json($validator->errors(), 400);
        }
        Log::info("Success validation");

        $data = $request->all();
        $id = $request->user['id'];
        $email = $request->user['email'];
        $name = $request->user['name'];
        $avatar = $request->user['photoUrl'];
        $password = rand(45648978,45678979452);
        if(!$user = User::where('provider_identificator',$id)->first()) {
            $user = new User();
        } else {
            $user->password = bcrypt($password);
            $user->role_id = $data['role_id'];
            $user->save();
            $request = new Request();
            Log::info(['email' => $email, 'password' => $password]);
            $request->request->add(['email' => $email, 'password' => $password]);
            $request->setMethod('POST');
            $response = $this->authenticate($request, true);
            Log::info(json_encode($response));
            if($user = auth('web')->user()) {
                return response()->json(['token' => $response['message']['token'], 'user' => new UsersResource($user)], $response['code']);
            }
            return response()->json($response['message'], $response['code']);
        }
        Log::info($user->id);
        $user->provider_identificator = $id;
        $user->email = $email;
        $user->provider_id = $providerData->id;
        $user->name = $name;
        $user->enabled = 1;
        $user->avatar = $avatar;
        $user->role_id = $data['role_id'];
        $user->avatar = $avatar;
        $user->password = bcrypt($password);
        $user->save();
        Log::info("Success sql queries");
        $request->request->add(['email' => $email, 'password' => $password]);
        $response = $this->authenticate($request, true);
        return response()->json($response['message'], $response['code']);
    }



    public function getAuthenticatedUser()
    {
        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }
        return new UsersResource($user);
//        return response()->json(compact('user'));
    }

    public function getAll()
    {
        return UsersResource::collection(User::withTrashed()->orderBy('created_at', 'DESC')->get());
    }

    public function verification(Request $request)
    {
        $rules = [];
        $rules['code'] = 'digits:4';
        $data = $request->all();
        if(isset($data['is_web'])) {
            $rules['code'] = 'digits:6';
            $rules['organization_id'] = 'required';
            $rules['job_id'] = 'required';
            $rules['year_id'] = 'required';
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $user = User::where('digit_number', $data['code'])->first();
        $is_new = true;
        if($user->enabled) {
            $is_new = false;
        }
        if (!$user) {
            return response()->json(['user_not_found'], 404);
        }
        $user->enabled = 1;
        $user->digit_number = '';
        $user->save();
        if(isset($data['is_web'])) {
            if(!WebUserJobInfo::where('user_id',$user->id)->where('organization_id',$data['organization_id'])->where('job_id',$data['job_id'])->where('year_id',$data['year_id'])->first()){
                $info = new WebUserJobInfo();
                $info->user_id = $user->id;
                $info->organization_id = $data['organization_id'];
                $info->job_id = $data['job_id'];
                $info->year_id = $data['year_id'];
                $info->save();

                $review = new JobReview();
                $review->user_id = $user->id;
                $review->job_id = $data['job_id'];
                $review->stars = $data['stars'];
                $review->who_you_are = $data['who_you_are'];
                $review->what_you_think = $data['what_you_think'];
                $review->save();

            }
        }
        if (isset($data['avatar']) && !empty($data['avatar'])) {
            if ($user->avatar) {
                unlink(storage_path('app/public/users/avatars/' . $user->avatar));
            }
            $user->avatar = $user->uploadAvatar($data['avatar']);
        }
        $user->save();
        $token = JWTAuth::fromUser($user);
            return response()->json(['user' => new UsersResource($user), 'token' => $token, 'is_new' => $is_new], 201);
    }



    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            return Socialite::driver($provider)->redirect();
        }

        $authUser = $this->findOrCreateUser($user);
        Auth::login($authUser, true);

        return Redirect::to('home');
    }

    private function sendSMSNotification($to = '', $body = '')
    {
        // Twillio SMS
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_number = getenv("TWILIO_NUMBER");
        $client = new Client($account_sid, $auth_token);
        $client->messages->create($to,
            ['from' => $twilio_number, 'body' => $body]);
    }

    private function generatePIN($digits = 4)
    {
        $i = 0; //counter
        $pin = ""; //our default pin is blank.
        while ($i < $digits) {
            //generate a random number between 0 and 9.
            $pin .= mt_rand(0, 9);
            $i++;
        }
        if (strlen($pin) != 4) {
            $this->generatePIN();
        }
        if (User::where('digit_number', $pin)->first()) {
            $this->generatePIN();
        }
        return $pin;
    }
}
